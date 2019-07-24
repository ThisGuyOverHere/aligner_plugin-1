<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 29/08/2018
 * Time: 11:08
 */

namespace Features\Aligner\Utils;

use Exceptions\ValidationError;
use Features\Aligner;
use Features\Aligner\Model\Projects_ProjectDao;
use Log;

class Alignment {

    use ProjectProgress;

    public $zero = 0;
    public $native = 0;
    public $approximated = 0;
    public $lessCommon = 0;
    public $long = 0;

    private $project = null;

    public function alignSegments($project, $source, $target, $source_lang, $target_lang) {
        // Save project data used later to update Progress
        $this->project = $project;

        // Variant on Church and Gale algorithm with Levenshtein distance
        $time_start = microtime(true);

        $source_translated = $this->translateSegments($source, $source_lang, $target_lang);

        $time_end = microtime(true);
        Log::doLog('Completed pre-translation: source ['.count($source).'] in '.($time_end-$time_start).' seconds');

        $original_clean = $this->cleanSegments($source);
        $source_clean = $this->cleanSegments($source_translated);
        $target_clean = $this->cleanSegments($target);

        $time_start = microtime(true);

        $indexes = $this->align($original_clean, $source_clean, $target_clean);
        $alignment = $this->mapAlignment($source, $target, $indexes);

        $time_end = microtime(true);
        Log::doLog('Completed alignment: source ['.count($source).'], target ['.count($target).'] in '.($time_end-$time_start).' seconds');
        Log::doLog('Used: ZERO-LENGTH ['.$this->zero.'], NATIVE ['.$this->native.'], APPROX ['.$this->approximated.'], COMMON ['.$this->lessCommon.'], LONG ['.$this->long.']');

        return $alignment;
    }

    // Pre-translate segments from source_lang to target_lang (it uses 'content_clean' format)
    function translateSegments($segments, $source_lang, $target_lang) {
        $result = [];

        $config = Aligner::getConfig();

        if( $config['TRANSLATION_ENGINE'] == "GoogleTranslate" ){
            $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
            $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
            $engineRecord->type = 'MT';

            $engine = new Engines_GoogleTranslate($engineRecord);
        } else {
            $engineDAO        = new \EnginesModel_EngineDAO( \Database::obtain() );
            $engineStruct     = \EnginesModel_EngineStruct::getStruct();
            $engineStruct->id = 1;

            $eng = $engineDAO->setCacheTTL( 60 * 5 )->read( $engineStruct );

            /**
             * @var $engineRecord EnginesModel_EngineStruct
             */
            $engineRecord = @$eng[0];

            $engine = new Engines_MyMemory($engineRecord);
        }

        // Progress - translate updates from 10 to 50
        $progress = 10;
        Projects_ProjectDao::updateField( $this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_4);
        $this->updateProgress($this->project->id, $progress);

        $input_segments = array();

        foreach ($segments as $segment) {
            $input_segments[] = array('segment'=>$segment['content_clean'], 'source'=>$source_lang, 'target'=>$target_lang);
        }

        $input_chunks = array_chunk($input_segments,$config['PARALLEL_CURL_TRANSLATIONS'],true);
        foreach ($input_chunks as $chunk){
            // Gets the translated segments and filters just the elements with the 'translation' key before merging
            $translation = $engine->getMulti($chunk);
            $translation = array_map(function ($ar) {return $ar['translation'];}, $translation);
            $result = array_merge($result,$translation);

            // Progress
            $progress = $progress + 40 * count($translation) / count($segments);
            $this->updateProgress($this->project->id, $progress);
        }

        foreach ($segments as $key => $segment){
            $segments[$key]['content_clean'] = $result[$key];
        }

        return $segments;
    }

    // Clean segments to remove useless characters
    function cleanSegments($segments) {
        $delimiters = '/\s|\?|\"|\“|\”|\.|\,|\;|\:|\!|\(|\)|\{|\}|\`|\’|\\\|\/|\||\'|\+|\-|\_/u';  // Why I need FULL UNICODE here? Why is not full UTF-8??

        $clean = [];

        foreach ($segments as $segment) {
            $text = $segment['content_clean'];
            $text = mb_strtolower($text, 'UTF-8');

            $elements = preg_split($delimiters, $text, null, PREG_SPLIT_NO_EMPTY);

            $clean[] = $elements;
        }

        return $clean;
    }

    // Perform alignment
    function align($original, $source, $target) {

        // Progress - align updates from 50 to 90
        $progress = 50;
        Projects_ProjectDao::updateField( $this->project, 'status_analysis', ConstantsJobAnalysis::ALIGN_PHASE_5);

        $matches = $this->find100x100Matches($original, $source, $target);

        Log::doLog('Found '.count($matches).' 100% matches');

        // No exact match has been found, we need to align entire document
        if (empty($matches)) {
            $alignment = $this->alignPart($source, $target, [0, 0]);
        } else {
            // Perform alignment on all sub-array, then merge them
            $alignment = [];
            $lastMatch = [0, 0];

            foreach ($matches as $match) {

                $time_start = microtime(true);

                $subSource = array_slice($source, $lastMatch[0], $match[0] - $lastMatch[0]);
                $subTarget = array_slice($target, $lastMatch[1], $match[1] - $lastMatch[1]);

                $subAlignment = $this->alignPart($subSource, $subTarget, $lastMatch);

                $alignment = array_merge($alignment, $subAlignment);  // Add alignments for sub-array
                $alignment[] = [[$match[0], 1], [$match[1], 1], 100];  // Add alignment for exact match

                $time_end = microtime(true);
                Log::doLog('Aligned sub-part: sub-source ['.count($subSource).'] from '.$lastMatch[0].', sub-target ['.count($subTarget).'] from '.$lastMatch[1].' in '.($time_end-$time_start).' seconds');

                // Update lastMatch to skip current match
                $lastMatch[0] = $match[0] + 1;
                $lastMatch[1] = $match[1] + 1;

                // Progress
                $progress = $progress + 40 * count($subSource) / count($source);
                $this->updateProgress($this->project->id, $progress);
            }

            // Align last part of documents, after last exact match
            $subSource = array_slice($source, $lastMatch[0]);
            $subTarget = array_slice($target, $lastMatch[1]);

            $subAlignment = $this->alignPart($subSource, $subTarget, $lastMatch);

            $alignment = array_merge($alignment, $subAlignment);  // Add alignments for sub-array
        }

        // Progress
        $this->updateProgress($this->project->id, 90);

        return $alignment;
    }

    // 100% matches
    function find100x100Matches($original, $source, $target) {
        // Try to find a 100% match (equal strings) and split align work in multiple sub-works (divide et impera)

        $original = array_map(function ($item) {
            return implode('', $item);
        }, $original);

        $source = array_map(function ($item) {
            return implode('', $item);
        }, $source);

        $target = array_map(function ($item) {
            return implode('', $item);
        }, $target);

        // 1. Find 100% matches from $original to $target, to find untranslatable matches

        $commonOT = array_intersect($original, $target);

        foreach ($commonOT as $key => $value) {  // Put these $original matches into $source, to force next run to find $source <> $target match
            $source[$key] = $value;
        }

        // 2. Find 100% matches from $source to $target, to find translatable matches

        // We need to perform it twice to have both indexes
        $commonST = array_intersect($source, $target);
        $commonTS = array_intersect($target, $source);

        $sourceIndexes = array_keys($commonST);
        $targetIndexes = array_keys($commonTS);

        // Indexes array are same size
        $commonSegments = [];

        $lastTargetIndex = -1;
        foreach ($sourceIndexes as $si) {
            foreach ($targetIndexes as $ti) {
                // Matches could be in different order, so we need to search the real match index
                // We cannot shuffle segments, so we check only for next indexes
                if ($source[$si] == $target[$ti] && $ti > $lastTargetIndex) {
                    $lastTargetIndex = $ti;
                    $commonSegments[] = [$si, $ti];  // Build pairs with source,target index of exact matches
                    break;
                }
            }
        }

        return $commonSegments;
    }

    // Sub-part alignment
    function alignPart($source, $target, $offset) {
        $MAX_NUMBER_OF_SEGMENTS = 16;

        if (count($source) < $MAX_NUMBER_OF_SEGMENTS && count($target) < $MAX_NUMBER_OF_SEGMENTS) {
            $scores = $this->buildScores($source, $target);
            $alignment = $this->extractPath($source, $target, $scores);
        } else {
            $alignment = $this->alignWindow($source, $target);
        }

        // Adjust offset
        $alignment = array_map(function ($item) use ($offset) {
            $item[0][0] += $offset[0];
            $item[1][0] += $offset[1];
            return $item;
        }, $alignment);

        return $alignment;
    }

    // Align by Window
    function alignWindow($source, $target) {

        $beadCosts = ['1-1' => 0, '2-1' => 150, '1-2' => 150, '0-1' => 50, '1-0' => 50];  // Penality for merge and holes
        $offsetCost = 10;  // For each position

        $WINDOW_SIZE = 8;  // 8 sentences before, 8 sentences after last matching point
        $CURRENT_OFFSET = 0;  // Updated when we found a match that causes an offset between source and target

        $sc = count($source);
        $tc = count($target);

        $res = [];

        // Devo gestire manualmente il caso in cui $target sia vuoto, che non è compatibile con l'algoritmo di sotto
        if ($tc == 0) {
            for ($i = 0; $i < $sc; $i++) {
                $res[] = [[$i, 1], [0, 0], 0];
            }

            return $res;
        }

        // Main algorithm
        for ($si = 1; $si <= $sc; $si++) {

            $match = null;

            $start = max($si + $CURRENT_OFFSET - $WINDOW_SIZE, 1);  // Limit to array start
            $end = min($si + $CURRENT_OFFSET + $WINDOW_SIZE, $tc);  // Limit to array end

            for ($ti = $start; $ti <= $end; $ti++) {

                foreach ($beadCosts as $pair => $beadCost) {
                    $sd = intval(substr($pair, 0, 1));
                    $td = intval(substr($pair, -1, 1));

                    if ($si - $sd >= 0 && $ti - $td >= 0) {

                        // Check if [$ti-$td, $td] is a contiguous interval we can merge, valid also for single segment
                        $evaluate = true;
                        for ($d = 1; $d <= $td; $d++) {
                            $evaluate = $evaluate & $target[$ti - $d] != null;
                        }

                        if (!$evaluate) {
                            continue;
                        }

                        $sources = array_slice($source, $si - $sd, $sd);
                        $targets = array_slice($target, $ti - $td, $td);

                        list($distance, $score) = $this->evalSentences($sources, $targets);
                        $cost = $distance + $beadCost + abs($si - $ti + $CURRENT_OFFSET) * $offsetCost;

                        $tuple = [$cost, $sd, $ti, $td, $score];

                        // Emulate min function on tuple
                        if ($match == null || $tuple[0] < $match[0]) {
                            $match = $tuple;
                        }
                    }
                }
            }

            // Use same format as Church and Gale
            list($cost, $sd, $ti, $td, $score) = $match;

            // If score is too low, choose a hole instead (only if it's not a hole on source)
            if ($score < 30 && $sd > 0) {
                $td = 0;
                $score = 0;
            }

            $res[] = [[$si - $sd, $sd], [$ti - $td, $td], $score];

            // Mark unavailable sentences for target (1 or 2, based on merge)
            for ($d = 1; $d <= $td; $d++) {
                $target[$ti - $d] = null;
            }

            // Update Offset
            $CURRENT_OFFSET = $ti - $si;

            // Adjust source index to eventually skip next sentence (for merge) or repeat current one (for target hole)
            $si--;  // Undo the for increment
            $si += $sd;  // Increment based on source distance
        }

        // Potrebbero esserci dei target rimasti fuori dall'algoritmo, andrebbero aggiunti come orfani nella "giusta" posizione
        for ($i = 0; $i < $tc; $i++) {
            if ($target[$i] != null) {

                // Search where to place this pending target
                foreach ($res as $key=>$value) {

                    // Place it before its successor
                    if ($value[1][0] > $i) {

                        // Add target in place
                        $item = [[$value[0][0], 0], [$i, 1], 0];

                        $before = array_slice($res, 0, $key );
                        $after = array_slice($res, $key );

                        $res = array_merge($before, [$item], $after);

                        $target[$i] = null;

                        break;
                    }
                }
            }
        }

        return $res;
    }

    // C&G build score matrix
    function buildScores($source, $target) {

        // $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];  // Penality for merge and holes
        $beadCosts = ['1-1' => 0, '2-1' => 150, '1-2' => 150, '0-1' => 50, '1-0' => 50];  // Penality for merge and holes

        $m = [];
        foreach (range(0, count($source)) as $si) {
            foreach (range(0, count($target)) as $ti) {
                if ($si == 0 && $ti == 0) {
                    $m[0][0] = [0, 0, 0, 0];
                } else {

                    $value = null;

                    foreach ($beadCosts as $pair => $beadCost) {
                        $sd = intval(substr($pair, 0, 1));
                        $td = intval(substr($pair, -1, 1));

                        if ($si - $sd >= 0 && $ti - $td >= 0) {

                            $sources = array_slice($source, $si-$sd, $sd);
                            $targets = array_slice($target, $ti-$td, $td);

                            list($distance, $score) = $this->evalSentences($sources, $targets);
                            $cost = $m[$si-$sd][$ti-$td][0] + $distance + $beadCost;

                            $tuple = [$cost, $sd, $td, $score];

                            // Emulate min function on tuple
                            if ($value == null || $tuple[0] < $value[0]) {
                                $value = $tuple;
                            }
                        }
                    }

                    $m[$si][$ti] = $value;
                }
            }
        }

        return $m;
    }

    // C&G extract best path into matrix
    function extractPath($source, $target, $scores) {
        $res = [];

        $si = count($source);
        $ti = count($target);

        while (true) {
            list($cost, $sd, $td, $score) = $scores[$si][$ti];

            if ($sd == 0 && $td == 0) {
                break;
            }

            $res[] = [[$si - $sd, $sd], [$ti - $td, $td], $score];

            $si -= $sd;
            $ti -= $td;
        }

        return array_reverse($res);
    }

    // Evaluate sentences
    function evalSentences($sources, $targets) {
        $costIns = 1; $costRep = 1; $costDel = 1;

        $source = segments_merge($sources);
        $target = segments_merge($targets);

        // Early check for empty segments
        if (count($source) == 0 || count($target) == 0) {
            $ss = implode('', $source);  // One of the two will be empty
            $ts = implode('', $target);

            $distance = mb_strlen($ss,'UTF-8') + mb_strlen($ts, 'UTF-8');

            $this->zero += 1;

            return [$distance, 0];
        }

        // Remove common words
        $commonWords = array_intersect_opt($source, $target);
        $commonChars = mb_strlen(implode('', $commonWords), 'UTF-8');

        $ss = array_diff_opt($source, $commonWords);
        $ts = array_diff_opt($target, $commonWords);

        // Put back to string to allow calculations (without spaces, so we optimize characters)
        $ss = implode('', $ss);
        $ts = implode('', $ts);

        // Distance
        $sl = strlen($ss);  // Do not use MULTI BYTE here because levenshtein considers 255 single byte length
        $tl = strlen($ts);

        if ($sl == 0 || $tl == 0) {  // Check if we can return immediately the upper bound
            $distance = max($sl, $tl);
            $this->zero += 1;
        } else if ($sl < 255 && $tl < 255) {  // Check if we can use the efficient standard implementation
            $distance = levenshtein($ss, $ts, $costIns, $costRep, $costDel);
            $this->native += 1;
        } else if (abs($sl - $tl) > 100) {  // Check if strings are too different, and return an approximated result
            $distance = abs($sl - $tl) + min($sl, $tl) / 2;  // Assuming 50% of the smaller string is different
            $this->approximated += 1;
        } else if ($commonChars / ($commonChars + max($sl, $tl)) < 0.15) {  // Common chars are less then 15% of the length of the original longest string
            $distance = abs($sl - $tl) + min($sl, $tl) / 2;  // Assuming 50% of the smaller string is different
            $this->lessCommon += 1;
        } else {
            $distance = levenshtein_opt($ss, $ts, $costIns, $costRep, $costDel);
            $this->long += 1;
        }

        // Score
        $sl = mb_strlen(implode('', $source), 'UTF-8');
        $tl = mb_strlen(implode('', $target), 'UTF-8');

        $len = min($sl, $tl);

        if ($len > 0) {
            $avg = ($sl + $tl) / 2;

            $score = 100 - 100 * min($distance / $avg, 1);  // Limit to 1 if distance > avg
        } else {
            $score = 0;
        }

        return [$distance, $score];
    }

    // Map Alignment to return
    function mapAlignment($source, $target, $indexes) {
        $alignment = [];

        foreach ($indexes as $index) {
            // Every index contains [[offset, length], [offset, length], score] of the source/target slice
            list(list($si, $sd), list($ti, $td), $s) = $index;

            $sa = array_slice($source, $si, $sd);
            $ta = array_slice($target, $ti, $td);

            $row = [
                'source' => (count($sa) == 1) ? $sa[0] : $sa,
                'target' => (count($ta) == 1) ? $ta[0] : $ta,
                'score' => $s
            ];

            $alignment[] = $row;
        }

        return $alignment;
    }

}

// Simple merge, for 1-N matches
function segments_merge($segments) {
    if (count($segments) == 0) {
        return [];
    } else if (count($segments) == 1) {
        return reset($segments);  // Here I'm not sure if array starts with index 0
    } else {
        $result = [];

        foreach ($segments as $segment) {
            $result = array_merge($result, $segment);
        }

        return $result;
    }
}

// Levensthein for 255+ chars strings
function levenshtein_opt($str1, $str2, $costIns, $costRep, $costDel) {

    $str1Array = preg_split('//u', $str1, null, PREG_SPLIT_NO_EMPTY);
    $str2Array = preg_split('//u', $str2, null, PREG_SPLIT_NO_EMPTY);

    $matrix = [];

    $str1Length = count($str1Array);
    $str2Length = count($str2Array);

    $row = [];
    $row[0] = 0.0;
    for ($j = 1; $j < $str2Length + 1; $j++) {
        $row[$j] = $j * $costIns;
    }

    $matrix[0] = $row;

    for ($i = 0; $i < $str1Length; $i++) {
        $row = [];
        $row[0] = ($i + 1) * $costDel;

        for ($j = 0; $j < $str2Length; $j++) {
            $row[$j + 1] = min(
                $matrix[$i][$j + 1] + $costDel,
                $row[$j] + $costIns,
                $matrix[$i][$j] + ($str1Array[$i] === $str2Array[$j] ? 0.0 : $costRep)
            );
        }

        $matrix[$i + 1] = $row;
    }

    return $matrix[$str1Length][$str2Length];
}

// Optimized function to avoid PHP ones
function array_diff_opt($words, $commonWords) {
    // Hash map of initial words
    $map = array();
    foreach($words as $word) $map[$word] = 1;

    // Unset commont words
    foreach($commonWords as $word) unset($map[$word]);

    // Return keys from map
    return array_keys($map);
}

function array_intersect_opt($a, $b) {
    // Hash map of $a words
    $map = array();
    foreach($a as $word) $map[$word] = 1;

    // Get words of $b found in $map
    $out = array();
    foreach($b as $word) if(isset($map[$word])) $out[$word] = 1;  // To avoid duplicates

    // Returm common words
    return array_keys($out);
}