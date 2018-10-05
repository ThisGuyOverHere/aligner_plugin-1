<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 29/08/2018
 * Time: 11:08
 */

namespace Features\Aligner\Utils;

use Features\Aligner;
use Log;

class Alignment {

    public function alignSegments($source, $target, $source_lang, $target_lang) {
        // Variant on Church and Gale algorithm with Levenshtein distance

        $time_start = microtime(true);

        $source_translated = $this->translateSegments($source, $source_lang, $target_lang);

        $time_end = microtime(true);
        Log::doLog('Completed pre-translation: source ['.count($source).'] in '.($time_end-$time_start).' seconds');

        $source_clean = $this->cleanSegments($source_translated);
        $target_clean = $this->cleanSegments($target);

        $time_start = microtime(true);

        $indexes = $this->align($source_clean, $target_clean);
        $alignment = $this->mapAlignment($source, $target, $indexes);

        $time_end = microtime(true);
        Log::doLog('Completed alignment: source ['.count($source).'], target ['.count($target).'] in '.($time_end-$time_start).' seconds');

        return $alignment;
    }

    // Pre-translate segments from source_lang to target_lang (it uses 'content_clean' format)
    function translateSegments($segments, $source_lang, $target_lang) {
        $result = [];

        $config = Aligner::getConfig();

        $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
        $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
        $engineRecord->type = 'MT';

        $engine = new Engines_GoogleTranslate($engineRecord);

        $input_segments = array();

        foreach ($segments as $segment) {
            $input_segments[] = array('segment'=>$segment['content_clean'], 'source'=>$source_lang, 'target'=>$target_lang);
        }

        $input_chunks = array_chunk($input_segments,$config['PARALLEL_CURL_TRANSLATIONS'],true);
        foreach ($input_chunks as $chunk){

            //Gets the translated segments and filters just the elements with the 'translation' key before merging
            $translation = $engine->getMulti($chunk);
            $translation = array_map(function ($ar) {return $ar['translation'];}, $translation);
            $result = array_merge($result,$translation);
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
            $text = strtolower($text);

            $elements = preg_split($delimiters, $text, null, PREG_SPLIT_NO_EMPTY);

            $clean[] = $elements;
        }

        return $clean;
    }

    // Perform alignment
    function align($source, $target) {

        $matches = $this->find100x100Matches($source, $target);

        Log::doLog('Found '.count($matches).' 100% matches');

        // No exact match has been found, we need to align entire document
        if (empty($matches)) {
            $alignment = $this->alignPart($source, $target, [0, 0]);

            return $alignment;
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
            }

            // Align last part of documents, after last exact match
            $subSource = array_slice($source, $lastMatch[0]);
            $subTarget = array_slice($target, $lastMatch[1]);

            $subAlignment = $this->alignPart($subSource, $subTarget, $lastMatch);

            $alignment = array_merge($alignment, $subAlignment);  // Add alignments for sub-array

            return $alignment;
        }
    }

    // 100% matches
    function find100x100Matches($source, $target) {
        // Try to find a 100% match (equal strings) and split align work in multiple sub-works (divide et impera)

        $source = array_map(function ($item) {
            return implode('', $item);
        }, $source);

        $target = array_map(function ($item) {
            return implode('', $item);
        }, $target);

        // We need to perform it twice to have both indexes
        $commonST = array_intersect($source, $target);
        $commonTS = array_intersect($target, $source);

        $sourceIndexes = array_keys($commonST);
        $targetIndexes = array_keys($commonTS);

        // Indexes array are same size
        $commonSegments = [];

        foreach ($sourceIndexes as $key => $value) {
            $commonSegments[] = [$value, $targetIndexes[$key]];  // Build pairs with source,target index of exact matches
        }

        return $commonSegments;
    }

    // Sub-part alignment
    function alignPart($source, $target, $offset) {
        $MAX_NUMBER_OF_SEGMENTS = 0;

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
        foreach ($res as $key=>$value) {
            $next = $value[1][0] + $value[1][1];  // Current target + 0, 1, 2 based on merge strategy

            if ($target[$next] != null) {
                // Add target in place
                $item = [[$value[0][0], 0], [$next, 1], 0];

                $before = array_slice($res, 0, $key + 1);
                $after = array_slice($res, $key + 1);

                $res = array_merge($before, [$item], $after);

                $target[$next] = null;
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

        // Remove common words
        $commonWords = array_intersect_opt($source, $target);

        $ss = array_diff_opt($source, $commonWords);
        $ts = array_diff_opt($target, $commonWords);

        // Put back to string to allow calculations (without spaces, so we optimize characters)
        $ss = implode('', $ss);
        $ts = implode('', $ts);

        // Distance
        $sl = strlen($ss);
        $tl = strlen($ts);

        if ($sl == 0 || $tl == 0) {  // Check if we can return immediately the upper bound
            $distance = max($sl, $tl);
        } else if ($sl < 255 && $tl < 255) {  // Check if we can use the efficient standard implementation
            $distance = levenshtein($ss, $ts, $costIns, $costRep, $costDel);
        } else if (abs($sl - $tl) > 100) {  // Check if strings are too different, and return an approximated result
            $distance = abs($sl - $tl) + min($sl, $tl) / 2;  // Assuming 50% of the little string is different
        } else {
            $distance = levenshtein_opt($ss, $ts, $costIns, $costRep, $costDel);
        }

        // Score
        $sl = strlen(implode('', $source));
        $tl = strlen(implode('', $target));

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

    $str1Array = str_split($str1, 1);
    $str2Array = str_split($str2, 1);

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
function array_diff_opt($a, $b) {
    $map = array();
    foreach($a as $val) $map[$val] = 1;
    foreach($b as $val) unset($map[$val]);
    return array_keys($map);
}

function array_intersect_opt($a, $b) {
    $index = array_flip($a);
    foreach ($b as $value) {
        if (isset($index[$value])) unset($index[$value]);
    }
    foreach ($index as $key => $value) {
        if (isset($a[$value])) unset($a[$value]);
    }
    return $a;
}
