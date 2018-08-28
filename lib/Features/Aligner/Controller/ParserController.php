<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/08/2018
 * Time: 13:33
 */


namespace Features\Aligner\Controller;
include_once \INIT::$UTILS_ROOT . "/xliff.parser.1.3.class.php";

use CatUtils;
use Exception;
use Features\Aligner;
use Features\Aligner\Model\Files_FileDao;


class ParserController extends AlignerController {


    /**
     * @throws Exception
     */
    public function jobParser() {

        $id_job = $this->params['id_job'];

        $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
        $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

        $source_lang = $source_file->language_code;
        $target_lang = $target_file->language_code;

        $source_segments = $this->_file2segments($source_file, $source_lang);
        $target_segments = $this->_file2segments($target_file, $target_lang);

        $version = $this->params['version'];

        switch ($version) {
            case 'v0':
                $alignment = $this->_alignSegmentsV0($source_segments, $target_segments);
                break;
            case 'v1':
                $alignment = $this->_alignSegmentsV1($source_segments, $target_segments);
                break;
            case 'v2':
                $alignment = $this->_alignSegmentsV2($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3':
                $alignment = $this->_alignSegmentsV3($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3b':
                $alignment = $this->_alignSegmentsV3B($source_segments, $target_segments, $source_lang, $target_lang);
                break;
            case 'v3c':
                $alignment = $this->_alignSegmentsV3C($source_segments, $target_segments, $source_lang, $target_lang);
                break;
        }

        // DEBUG //
//        $this->response->json( ['res' => $alignment] );

        // Format alignment for frontend test purpose
        $source = array_map(function ($index, $item) {
            return [
                'clean' => $item['source']['clean'],
                'raw' => $item['source']['raw'],
                'order' => ($index + 1)* 1000000000,
                'next' => ($index + 2) * 1000000000
                ];
        }, array_keys($alignment), $alignment);

        $target = array_map(function ($index, $item) {
            return [
                'clean' => $item['target']['clean'],
                'raw' => $item['target']['raw'],
                'order' => ($index + 1)* 1000000000,
                'next' => ($index + 2) * 1000000000
            ];
        }, array_keys($alignment), $alignment);

        $source[count($source)-1]['next'] = null;
        $target[count($target)-1]['next'] = null;

        $this->response->json( ['source' => $source, 'target' => $target] );
    }


    /**
     * @param $file
     * @param $lang
     * @return array
     * @throws Exception
     */
    protected function _file2segments($file, $lang) {
        list($date, $sha1) = explode("/", $file->sha1_original_file);

        // Get file content
        try {
            $fileStorage = new \FilesStorage;
            $xliff_file = $fileStorage->getXliffFromCache($sha1, $file->language_code);
            $xliff_content = file_get_contents($xliff_file);
        } catch ( Exception $e ) {
            throw new Exception( $file, $e->getCode(), $e );
        }

        // Parse xliff
        try {
            $parser = new \Xliff_Parser;
            $xliff = $parser->Xliff2Array($xliff_content);
        } catch ( Exception $e ) {
            throw new Exception( $file, $e->getCode(), $e );
        }

        // Checking that parsing went well
        if ( isset( $xliff[ 'parser-errors' ] ) or !isset( $xliff[ 'files' ] ) ) {
            throw new Exception( $file, -4 );
        }

        // Creating the Segments
        $segments = array();

        foreach ( $xliff[ 'files' ] as $xliff_file ) {

            // An xliff can contains multiple files (docx has style, settings, ...) but only some with useful trans-units
            if ( !array_key_exists( 'trans-units', $xliff_file ) ) {
                continue;
            }

            foreach ($xliff_file[ 'trans-units' ] as $trans_unit) {

                // Extract only raw-content
                $unit_segments = array_map(function ($item) {
                    return $item['raw-content'];
                }, $trans_unit[ 'seg-source' ]);

                // Build an object with raw-content and clean-content
                $unit_segments = array_map(function ($item) use ($lang) {
                    return [
                        'raw' => $item,
                        'clean' => $this->_cleanSegment($item, $lang),
                        'words' => $this->_countWordsInSegment($item, $lang)
                    ];
                }, $unit_segments);

                // Append to existing Segments
                $segments = array_merge($segments, $unit_segments);
            }
        }

        return $segments;
    }

    /**
     *
     * Code almost cloned from CatUtils::placehold_xliff_tags()
     *
     * @param $segment
     * @param $lang
     * @return null|string|string[]
     */
    protected function _cleanSegment($segment, $lang) {

        $tagsRegex = [
            '|(</x>)|si',
            '|<(g\s*id=["\']+.*?["\']+\s*[^<>]*?)>|si',
            '|<(/g)>|si',
            '|<(x .*?/?)>|si',
            '#<(bx[ ]{0,}/?|bx .*?/?)>#si',
            '#<(ex[ ]{0,}/?|ex .*?/?)>#si',
            '|<(bpt\s*.*?)>|si',
            '|<(/bpt)>|si',
            '|<(ept\s*.*?)>|si',
            '|<(/ept)>|si',
            '|<(ph .*?)>|si',
            '|<(/ph)>|si',
            '|<(it .*?)>|si',
            '|<(/it)>|si',
            '|<(mrk\s*.*?)>|si',
            '|<(/mrk)>|si'
        ];

        foreach ($tagsRegex as $regex) {
            $segment = preg_replace($regex, '', $segment);
        }

        return $segment;
    }

    /**
     * @param $segment
     * @param $lang
     * @return float|int
     */
    protected function _countWordsInSegment($segment, $lang) {
        $wordCount = CatUtils::segment_raw_wordcount( $segment, $lang );

        return $wordCount;
    }

    /**
     * Naive algorithm, it only puts side by side source and target
     *
     * @param $source
     * @param $target
     * @return array
     */
    protected function _alignSegmentsV0($source, $target) {

        $alignment = array();

        $source_length = count($source);
        $target_length = count($target);

        $index = 0;
        while (true) {
            if ($index < $source_length && $index < $target_length) {
                $row = [
                    'source' => $source[$index],
                    'target' => $target[$index]
                ];

                $alignment[] = $row;
            } else if ($index < $source_length) {
                $row = [
                    'source' => $source[$index],
                    'target' => null
                ];

                $alignment[] = $row;
            } else if ($index < $target_length) {
                $row = [
                    'source' => null,
                    'target' => $target[$index]
                ];

                $alignment[] = $row;
            } else {
                break;
            }

            $index++;
        }

        return $alignment;
    }

    /**
     * Original Church and Gale algorithm
     *
     * @param $source
     * @param $target
     * @return array
     */
    protected function _alignSegmentsV1($source, $target) {

        // Utility functions for algorithm
        function sentenceLength($sentence) {
            return strlen(str_replace(' ', '', $sentence));
        }

        function calculateMean($source, $target) {
            // Caluclate mean length: mean = len(trgfile) / len(srcfile)

            $sourceLength = array_reduce($source, function ($carry, $item) {
                $carry += sentenceLength($item['clean']);
                return $carry;
            }, 0);

            $targetLength = array_reduce($target, function ($carry, $item) {
                $carry += sentenceLength($item['clean']);
                return $carry;
            }, 0);

            return $targetLength / $sourceLength;
        }

        function _align($sourceLengths, $targetLengths, $mean, $variance, $beadCosts) {
            // Math utils functions
            function normCDF($value) {
                $t = 1 / (1 + 0.2316419 * $value);

                $probdist = 1 - 0.3989423 * exp(-$value * $value / 2) * (0.319381530 * $t - 0.356563782 * pow($t, 2) + 1.781477937 * pow($t, 3) - 1.821255978 * pow($t, 4) + 1.330274429 * pow($t, 5));

                return $probdist;
            }

            function normLogs($value) {
                try {
                    return log(1 - normCDF($value));
                } catch (\Exception $e) {
                    return -INF;
                }
            }

            function lengthCost($sourceLengths, $targetLengths, $mean, $variance) {
                $sl = array_sum($sourceLengths);
                $tl = array_sum($targetLengths);

                $m = ($sl + $tl * $mean) / 2;

                if (sqrt($m * $variance) == 0) {
                    return -INF;
                } else {
                    $delta = ($sl - $tl * $mean) / sqrt($m * $variance);

                    return -100 * (log(2) + normLogs(abs($delta)));
                }
            }


            $m = [];
            foreach (range(0, count($sourceLengths)) as $si) {
                foreach (range(0, count($targetLengths)) as $ti) {
                    if ($si == 0 && $ti == 0) {
                        $m['0-0'] = [0, 0, 0];
                    } else {

                        $value = null;

                        foreach ($beadCosts as $pair => $beadCost) {
                            $sd = intval(substr($pair, 0, 1));
                            $td = intval(substr($pair, -1, 1));

                            if ($si - $sd >= 0 && $ti - $td >= 0) {
                                $tuple = [
                                    $m[($si-$sd).'-'.($ti-$td)][0] + lengthCost(array_slice($sourceLengths, $si-$sd, $sd), array_slice($targetLengths, $ti-$td, $td), $mean, $variance) + $beadCost,
                                    $sd,
                                    $td
                                ];

                                // Emulate min function on tuple
                                if ($value == null || $tuple[0] < $value[0]) {
                                    $value = $tuple;
                                }
                            }
                        }

                        $m[$si.'-'.$ti] = $value;
                    }
                }
            }

            $res = [];

            $si = count($sourceLengths);
            $ti = count($targetLengths);

            while (true) {
                list($c, $sd, $td) = $m[$si.'-'.$ti];

                if ($sd == 0 && $td == 0) {
                    break;
                }

                $res[] = [[$si - $sd, $sd], [$ti - $td, $td]];

                $si -= $sd;
                $ti -= $td;
            }

            return $res;
        }

        // Simple merge, for 2-1 or 1-2 matches
        function mergeSegments($segments) {
            if (count($segments) == 1) {
                return $segments[0];
            } else {
                return array_reduce($segments, function ($carry, $item) {
                    $carry['raw'] .= $item['raw'];
                    $carry['clean'] .= $item['clean'];
                    $carry['words'] += $item['words'];

                    return $carry;
                }, ['raw' => '', 'clean' => '', 'words' => 0]);
            }
        }


        // Basic C&G algorithm, with mean=1.0 and variance=6.8  <-- They should be calculated on documents
        $mean = calculateMean($source, $target);
        $variance = 6.8;
        $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];

        $sourceLengths = array_map(function ($item) { return sentenceLength($item['clean']); }, $source);
        $targetLengths = array_map(function ($item) { return sentenceLength($item['clean']); }, $target);

        $indexes = _align($sourceLengths, $targetLengths, $mean, $variance, $beadCosts);
        $indexes = array_reverse($indexes);

        $alignment = [];
        foreach ($indexes as $index) {  // Every index contains [[offset, length], [offset, length]] of the source/target slice
            $si = $index[0][0];
            $ti = $index[1][0];

            $sd = $index[0][1];
            $td = $index[1][1];

            $row = [
                'source' => mergeSegments(array_slice($source, $si, $sd)),
                'target' => mergeSegments(array_slice($target, $ti, $td))
            ];

            $alignment[] = $row;
        }

        return $alignment;
    }

    /**
     * Using pre-translation on Church and Gale algorithm
     *
     * @param $source
     * @param $target
     * @param $source_lang
     * @param $target_lang
     * @return array
     */
    protected function _alignSegmentsV2($source, $target, $source_lang, $target_lang) {

        // Utility functions for algorithm
        function sentenceLength($sentence) {
            return strlen(str_replace(' ', '', $sentence));
        }

        function _align($sourceLengths, $targetLengths, $mean, $variance, $beadCosts) {
            // Math utils functions
            function normCDF($value) {
                $t = 1 / (1 + 0.2316419 * $value);

                $probdist = 1 - 0.3989423 * exp(-$value * $value / 2) * (0.319381530 * $t - 0.356563782 * pow($t, 2) + 1.781477937 * pow($t, 3) - 1.821255978 * pow($t, 4) + 1.330274429 * pow($t, 5));

                return $probdist;
            }

            function normLogs($value) {
                try {
                    return log(1 - normCDF($value));
                } catch (\Exception $e) {
                    return -INF;
                }
            }

            function lengthCost($sourceLengths, $targetLengths, $mean, $variance) {
                $sl = array_sum($sourceLengths);
                $tl = array_sum($targetLengths);

                $m = ($sl + $tl * $mean) / 2;

                if (sqrt($m * $variance) == 0) {
                    return -INF;
                } else {
                    $delta = ($sl - $tl * $mean) / sqrt($m * $variance);

                    return -100 * (log(2) + normLogs(abs($delta)));
                }
            }


            $m = [];
            foreach (range(0, count($sourceLengths)) as $si) {
                foreach (range(0, count($targetLengths)) as $ti) {
                    if ($si == 0 && $ti == 0) {
                        $m['0-0'] = [0, 0, 0];
                    } else {

                        $value = null;

                        foreach ($beadCosts as $pair => $beadCost) {
                            $sd = intval(substr($pair, 0, 1));
                            $td = intval(substr($pair, -1, 1));

                            if ($si - $sd >= 0 && $ti - $td >= 0) {
                                $tuple = [
                                    $m[($si-$sd).'-'.($ti-$td)][0] + lengthCost(array_slice($sourceLengths, $si-$sd, $sd), array_slice($targetLengths, $ti-$td, $td), $mean, $variance) + $beadCost,
                                    $sd,
                                    $td
                                ];

                                // Emulate min function on tuple
                                if ($value == null || $tuple[0] < $value[0]) {
                                    $value = $tuple;
                                }
                            }
                        }

                        $m[$si.'-'.$ti] = $value;
                    }
                }
            }

            $res = [];

            $si = count($sourceLengths);
            $ti = count($targetLengths);

            while (true) {
                list($c, $sd, $td) = $m[$si.'-'.$ti];

                if ($sd == 0 && $td == 0) {
                    break;
                }

                $res[] = [[$si - $sd, $sd], [$ti - $td, $td]];

                $si -= $sd;
                $ti -= $td;
            }

            return $res;
        }

        // Simple merge, for 2-1 or 1-2 matches
        function mergeSegments($segments) {
            if (count($segments) == 1) {
                return $segments[0];
            } else {
                return array_reduce($segments, function ($carry, $item) {
                    $carry['raw'] .= $item['raw'];
                    $carry['clean'] .= $item['clean'];
                    $carry['words'] += $item['words'];

                    return $carry;
                }, ['raw' => '', 'clean' => '', 'words' => 0]);
            }
        }

        function translateSegment($segment, $source_lang, $target_lang) {
            $config = Aligner::getConfig();

            $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
            $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
            $engineRecord->type = 'MT';

            $engine = new \Engines_GoogleTranslate($engineRecord);

            $res = $engine->get([
                'source' => $source_lang,
                'target' => $target_lang,
                'segment' => $segment
            ]);

            return $res['translation'];
        }

        // Pre-translate segments from source_lang to target_lang
        function translateSegments($segments, $source_lang, $target_lang) {
            $result = [];

            foreach ($segments as $segment) {
                $segment['clean'] = translateSegment($segment['clean'], $source_lang, $target_lang);
                $result[] = $segment;
            }

            return $result;
        }

        // Variant on basic C&G algorithm, with mean=1.0 and variance=6.8  <--  We are aligning on the same language
        $source_translated = translateSegments($source, $source_lang, $target_lang);

        $mean = 1;
        $variance = 6.8;
        $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];

        $sourceLengths = array_map(function ($item) { return sentenceLength($item['clean']); }, $source_translated);
        $targetLengths = array_map(function ($item) { return sentenceLength($item['clean']); }, $target);

        $indexes = _align($sourceLengths, $targetLengths, $mean, $variance, $beadCosts);
        $indexes = array_reverse($indexes);

        $alignment = [];
        foreach ($indexes as $index) {  // Every index contains [[offset, length], [offset, length]] of the source/target slice
            $si = $index[0][0];
            $ti = $index[1][0];

            $sd = $index[0][1];
            $td = $index[1][1];

            $row = [
                'source' => mergeSegments(array_slice($source, $si, $sd)),
                'target' => mergeSegments(array_slice($target, $ti, $td))
            ];

            $alignment[] = $row;
        }

        return $alignment;
    }

    /**
     *  Alignment based on editing distance
     *
     * @param $source
     * @param $target
     * @param $source_lang
     * @param $target_lang
     * @return array
     */
    protected function _alignSegmentsV3($source, $target, $source_lang, $target_lang) {

        function long_levenshtein($str1, $str2, $costIns = 1.0, $costRep = 1.0, $costDel = 1.0) {

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

        function addToAlignment(&$alignment, $pair) {
            if (!empty($pair[0]) && !empty($pair[1])) {
                $alignment[] = $pair;
            }
        }

        // Simple merge, for 1-N matches
        function mergeSegments($segments) {
            if (count($segments) == 1) {
                return reset($segments);  // Here I'm not sure if array starts with index 0
            } else {
                return array_reduce($segments, function ($carry, $item) {
                    $carry['raw'] = trim($carry['raw'] . ' ' . $item['raw']);
                    $carry['clean'] = trim($carry['clean'] . ' ' . $item['clean']);
                    $carry['words'] += $item['words'];

                    return $carry;
                }, ['raw' => '', 'clean' => '', 'words' => 0]);
            }
        }

        // get a list of (ID,Sentence) tuples and generate bi- or tri-sentence tuples
        function createSegmentTuples($segments, $size) {
            $res = [];

            for ($i = 0; $i <= count($segments) - $size; $i++) {
                $ids = [];
                $segs = [];

                foreach (array_slice($segments, $i, $i + $size) as $sublist) {
                    foreach ($sublist[0] as $k) {
                        $ids[] = $k;
                    }

                    $segs[] = $sublist[1];
                }

                $res[] = [$ids, mergeSegments($segs)];
            }

            return $res;
        }

        function eval_sents($source, $target) {

            $nbest = 5;

            $scoredict = [];

            foreach ($source as $si => $ss) {
                $scores = [];

                foreach ($target as $ti => $ts) {

                    // Calculate score here, using Levenshtein distance
                    // We need a value higher for better alignment
                    $score = 1000 - long_levenshtein($ss['clean'], $ts['clean']);
                    $scores[] = [$ti, $score];
                }

                // Sort scores ascending based on value
                usort($scores, function ($a, $b) {
                    return $a[1] < $b[1];
                });

                $scores = array_slice($scores, 0, $nbest);  // Keep only top alternatives

                $scoredict[$si] = $scores;
            }

            return $scoredict;
        }

        // apply heuristics to align all sentences that remain unaligned after finding best path of 1-to-1 alignments
        function gapfiller(&$alignment, $source, $target, $sourcegap, $targetgap, $pregap, $postgap) {
            $evalsrc = [];
            $evaltrg = [];

            // compile list of sentences in gap that will be considered for BLEU comparison

            // concatenate all sentences in pregap alignment pair
            $tmpsegs = array_reduce($pregap[0], function ($carry, $item) use ($source) {
                $carry[] = $source[$item];
                return $carry;
            }, []);
            $evalsrc[] = [$pregap[0], mergeSegments($tmpsegs)];

            #concatenate all sentences in pregap alignment pair
            $tmpsegs = array_reduce($pregap[1], function ($carry, $item) use ($target) {
                $carry[] = $target[$item];
                return $carry;
            }, []);
            $evaltrg[] = [$pregap[1], mergeSegments($tmpsegs)];

            // search will be pruned to this window
            $nto1 = 2;
            $window = 10 + $nto1;

            foreach ($sourcegap as $index => $value) {
                if ($index < $window || count($sourcegap) - $index <= $window) {
                    $sent = $source[$value];
                    $evalsrc[] = [[$value], $sent];
                }
            }

            foreach ($targetgap as $index => $value) {
                if ($index < $window || count($targetgap) - $index <= $window) {
                    $sent = $target[$value];
                    $evaltrg[] = [[$value], $sent];
                }
            }

            // concatenate all sentences in postgap alignment pair
            $tmpsegs = array_reduce($postgap[0], function ($carry, $item) use ($source) {
                $carry[] = $source[$item];
                return $carry;
            }, []);
            $evalsrc[] = [$postgap[0], mergeSegments($tmpsegs)];

            // concatenate all sentences in postgap alignment pair
            $tmpsegs = array_reduce($postgap[1], function ($carry, $item) use ($target) {
                $carry[] = $target[$item];
                return $carry;
            }, []);
            $evaltrg[] = [$postgap[1], mergeSegments($tmpsegs)];



            $nsrc = [];
            foreach (range(2, $nto1) as $n) {
                $nsrc[$n] = createSegmentTuples($evalsrc, $n);
                $evalsrc = array_merge($evalsrc, $nsrc[$n]);
            }

            $ntrg = [];
            foreach (range(2, $nto1) as $n) {
                $ntrg[$n] = createSegmentTuples($evaltrg, $n);
                $evaltrg = array_merge($evaltrg, $ntrg[$n]);
            }

            $evalsrc_raw = array_map(function ($item) { return $item[1]; }, $evalsrc);
            $evaltrg_raw = array_map(function ($item) { return $item[1]; }, $evaltrg);

            $scoredict_raw = eval_sents($evalsrc_raw, $evaltrg_raw);

            $scoredict = [];
            foreach ($scoredict_raw as $src => $value) {
                $srcs = $evalsrc[$src][0];

                if (!empty($value)) {
                    foreach ($value as $item) {
                        $trg = $item[0];
                        $score = $item[1];

                        $trgs = $evaltrg[$trg][0];

                        $scoredict[] = [$srcs, $trgs, $score];
                    }
                }
            }



            while (!empty($sourcegap) or !empty($targetgap)) {
                $pregapsrc = $pregap[0];
                $pregaptarget = $pregap[1];

                $postgapsrc = $postgap[0];
                $postgaptarget = $postgap[1];

                if (!empty($sourcegap)) {

                    // try if concatenating source sentences together improves score (beginning of gap)
                    if (!empty($pregapsrc)) {
                        $olditem = reset(array_filter($scoredict, function ($item) use ($pregapsrc) {
                            return $item[0] === $pregapsrc;
                        }));

                        $oldtarget = $olditem[1];
                        $oldscore = $olditem[2];

                        $srcs = array_merge($pregapsrc, [$sourcegap[0]]);

                        $newitem = reset(array_filter($scoredict, function ($item) use ($srcs) {
                            return $item[0] === $srcs;
                        }));

                        if ($newitem) {
                            $newtarget = $newitem[1];
                            $newscore = $newitem[2];

                            if ($newscore > $oldscore && $newtarget == $pregaptarget) {
                                $pregap = [$srcs, $pregaptarget];
                                array_shift($sourcegap);
                                continue;
                            }
                        }
                    }

                    // try if concatenating source sentences together improves score (end of gap)
                    if (!empty($postgapsrc)) {
                        $olditem = reset(array_filter($scoredict, function ($item) use ($postgapsrc) {
                            return $item[0] === $postgapsrc;
                        }));

                        $oldtarget = $olditem[1];
                        $oldscore = $olditem[2];

                        $srcs = array_merge([$sourcegap[count($sourcegap) - 1]], $postgapsrc);

                        $newitem = reset(array_filter($scoredict, function ($item) use ($srcs) {
                            return $item[0] === $srcs;
                        }));

                        if ($newitem) {
                            $newtarget = $newitem[1];
                            $newscore = $newitem[2];

                            if ($newscore > $oldscore && $newtarget == $postgaptarget) {
                                $postgap = [$srcs, $postgaptarget];
                                array_shift($sourcegap);
                                continue;
                            }
                        }
                    }

                }

                if (!empty($targetgap)) {

                    // try if concatenating target sentences together improves score (beginning of gap)
                    if (!empty($pregapsrc)) {
                        $newitem = reset(array_filter($scoredict, function ($item) use ($pregapsrc) {
                            return $item[0] === $pregapsrc;
                        }));

                        $newtarget = $newitem[1];
                        $newscore = $newitem[2];

                        if ($newtarget !== $pregaptarget && $newtarget !== $postgaptarget) {
                            $pregap = [$pregapsrc, $newtarget];

                            $targetgap = array_filter($targetgap, function ($item) use ($newtarget) {
                                return !in_array($item, $newtarget);
                            });

                            continue;
                        }
                    }

                    // try if concatenating target sentences together improves score (end of gap)
                    if (!empty($postgapsrc)) {
                        $newitem = reset(array_filter($scoredict, function ($item) use ($postgapsrc) {
                            return $item[0] === $postgapsrc;
                        }));

                        $newtarget = $newitem[1];
                        $newscore = $newitem[2];

                        if ($newtarget !== $postgaptarget && $newtarget !== $pregaptarget) {
                            $postgap = [$postgapsrc, $newtarget];

                            $targetgap = array_filter($targetgap, function ($item) use ($newtarget) {
                                return !in_array($item, $newtarget);
                            });

                            continue;
                        }
                    }
                }

                // concatenation didn't help, and we still have possible one-to-one alignments
                if (!empty($sourcegap) && !empty($targetgap)) {

                    // align first two sentences if score exists
                    $bestmatch = reset(array_filter($scoredict, function ($item) use ($sourcegap, $targetgap) {
                        return $item[0] === [$sourcegap[0]] && $item[1] == [$targetgap[0]];
                    }));

                    if ($bestmatch) {
                        addToAlignment($alignment, $pregap);
                        $pregap = [$bestmatch[0], $bestmatch[1]];
                        array_shift($sourcegap);
                        array_shift($targetgap);
                        continue;
                    }
                }

                break;
            }

            $exists = reset(array_filter($alignment, function ($item) use ($pregap) {
                return $item[0] === $pregap;
            }));

            if (!$exists) {
                addToAlignment($alignment, $pregap);
            }

            return $postgap;
        }

        // find unaligned sentences and create work packets for gapfiller()
        // gapfiller() takes two sentence pairs and all unaligned sentences in between as arguments; gapfinder() extracts these.
        function gapfinder($source, $target, $paths) {

            $alignment = [];

            // find gaps: lastpair is considered pre-gap, pair is post-gap
            $lastpair = [[], []];

            // if alignment is empty, gap will start at 0
            $src = -1;
            $trg = -1;

            foreach ($paths as $pair) {
                $src = $pair[0];
                $trg = $pair[1];

                $old_src = $lastpair[0];
                $old_trg = $lastpair[1];

                #in first iteration, gap will start at 0
                if (empty($old_src)) {
                    $old_src = [-1];
                }
                if (empty($old_trg)) {
                    $old_trg = [-1];
                }

                // identify gap sizes
                $source_gap = [];
                $target_gap = [];

                if ($old_src[count($old_src) - 1] + 1 < $src) {
                    $source_gap = range($old_src[count($old_src) - 1] + 1, $src - 1);
                }

                if ($old_trg[count($old_trg) - 1] + 1 < $trg) {
                    $target_gap = range($old_trg[count($old_trg) - 1] + 1, $trg - 1);
                }

                if (!empty($source_gap) || !empty($target_gap)) {
                    $lastpair = gapfiller($alignment, $source, $target, $source_gap, $target_gap, $lastpair, [[$src], [$trg]]);
                } else {
                    addToAlignment($alignment, $lastpair);
                    $lastpair = [[$src], [$trg]];
                }
            }

            // search for gap after last alignment pair
            $source_gap = [];
            $target_gap = [];

            if ($src + 1 < count($source)) {
                $source_gap = range($src + 1, count($source) - 1);
            }

            if ($trg + 1 < count($target)) {
                $target_gap = range($trg + 1, count($target) - 1);
            }


            if (!empty($source_gap) || !empty($target_gap)) {
                $lastpair = gapfiller($alignment, $source, $target, $source_gap, $target_gap, $lastpair, [[], []]);
            }

            addToAlignment($alignment, $lastpair);

            return $alignment;
        }

        // follow the backpointers in score matrix to extract best path of 1-to-1 alignments
        function extract_best_path($pointers) {

            $i = count($pointers) - 1;
            $j = count($pointers[0]) - 1;

            $best_path = [];

            while ($i >= 0 && $j >= 0) {
                $pointer = $pointers[$i][$j];

                if ($pointer == '^') {
                    $i -= 1;
                } else if ($pointer == '<') {
                    $j -= 1;
                } else if ($pointer == 'match') {
                    $best_path[] = [$i, $j];
                    $i -= 1;
                    $j -= 1;
                }
            }

            return array_reverse($best_path);
        }

        // dynamic programming search for best path of alignments (maximal score)
        function pathfinder($source, $target, $scoredict) {

             // add an extra row/column to the matrix and start filling it from 1,1 (to avoid exceptions for first row/column)
             $matrix = [];
             for ($row = 0; $row <= count($source); $row++) {
                 for ($column = 0; $column <= count($target); $column++) {
                     $matrix[$row][$column] = 0;
                 }
             }

            $pointers = [];
            for ($row = 0; $row < count($source); $row++) {
                for ($column = 0; $column < count($target); $column++) {
                    $pointers[$row][$column] = '';
                }
            }

            for ($i = 0; $i < count($source); $i++) {
                $alignments = $scoredict[$i];

                for ($j = 0; $j < count($target); $j++) {
                    $best_score = $matrix[$i][$j+1];
                    $best_pointer = '^';

                    $score = $matrix[$i+1][$j];

                    if ($score > $best_score) {
                        $best_score = $score;
                        $best_pointer = '<';
                    }

                    $alignment = reset(array_filter($alignments, function ($item) use ($j) {
                       return $item[0] == $j;
                    }));

                    if ($alignment) {
                        $score = $alignment[1] + $matrix[$i][$j];

                        if ($score > $best_score) {
                            $best_score = $score;
                            $best_pointer = 'match';
                        }
                    }

                    $matrix[$i+1][$j+1] = $best_score;
                    $pointers[$i][$j] = $best_pointer;
                }
            }

            return extract_best_path($pointers);
        }

        function translateSegment($segment, $source_lang, $target_lang) {
            $config = Aligner::getConfig();

            $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
            $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
            $engineRecord->type = 'MT';

            $engine = new \Engines_GoogleTranslate($engineRecord);

            $res = $engine->get([
                'source' => $source_lang,
                'target' => $target_lang,
                'segment' => $segment
            ]);

            return $res['translation'];
        }

        // Pre-translate segments from source_lang to target_lang
        function translateSegments($segments, $source_lang, $target_lang) {
            $result = [];

            foreach ($segments as $segment) {
                $segment['clean'] = translateSegment($segment['clean'], $source_lang, $target_lang);
                $result[] = $segment;
            }

            return $result;
        }

        // Variant on Bleu Align algorithm with Levenshtein distance
        $source_translated = translateSegments($source, $source_lang, $target_lang);

        $scoredict = eval_sents($source_translated, $target);
        $paths = pathfinder($source_translated, $target, $scoredict);
        $indexes = gapfinder($source_translated, $target, $paths);

        $alignment = [];
        foreach ($indexes as $index) {  // Every index contains [[sources...], [targets...]]
            $si = $index[0];
            $ti = $index[1];

            $ss = [];
            $ts = [];

            // We cannot use array_filter with flag here, it's available only on PHP 5.6+
            foreach ($source as $key => $value) {
                if (in_array($key, $si)) {
                    $ss[] = $value;
                }
            }

            foreach ($target as $key => $value) {
                if (in_array($key, $ti)) {
                    $ts[] = $value;
                }
            }

            $row = [
                'source' => mergeSegments($ss),
                'target' => mergeSegments($ts)
            ];

            $alignment[] = $row;
        }

        return $alignment;
   }

    /**
     *  Alignment based on Church and Gale with editing distance scoring
     *
     * @param $source
     * @param $target
     * @param $source_lang
     * @param $target_lang
     * @return array
     */
    protected function _alignSegmentsV3B($source, $target, $source_lang, $target_lang) {

        function long_levenshtein($str1, $str2, $costIns, $costRep, $costDel) {

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

        // Simple merge, for 1-N matches
        function mergeSegments($segments) {
            if (count($segments) == 1) {
                return reset($segments);  // Here I'm not sure if array starts with index 0
            } else {
                return array_reduce($segments, function ($carry, $item) {
                    $carry['raw'] = trim($carry['raw'] . ' ' . $item['raw']);
                    $carry['clean'] = trim($carry['clean'] . ' ' . $item['clean']);
                    $carry['words'] += $item['words'];

                    return $carry;
                }, ['raw' => '', 'clean' => '', 'words' => 0]);
            }
        }

        function eval_sents($sources, $targets) {
            $costIns = 1; $costRep = 1; $costDel = 1;

            $ss = mergeSegments($sources)['clean'];
            $ts = mergeSegments($targets)['clean'];

            if (strlen($ss) < 255 && strlen($ts) < 255) {
                return levenshtein($ss, $ts, $costIns, $costRep, $costDel);
            } else {
                return long_levenshtein($ss, $ts, $costIns, $costRep, $costDel);
            }
        }

        function align($source, $target) {

            $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];  // Penality for merge and holes

            $m = [];
            foreach (range(0, count($source)) as $si) {
                foreach (range(0, count($target)) as $ti) {
                    if ($si == 0 && $ti == 0) {
                        $m[0][0] = [0, 0, 0];
                    } else {

                        $value = null;

                        foreach ($beadCosts as $pair => $beadCost) {
                            $sd = intval(substr($pair, 0, 1));
                            $td = intval(substr($pair, -1, 1));

                            if ($si - $sd >= 0 && $ti - $td >= 0) {

                                $sources = array_slice($source, $si-$sd, $sd);
                                $targets = array_slice($target, $ti-$td, $td);

                                $score = $m[$si-$sd][$ti-$td][0] + eval_sents($sources, $targets) + $beadCost;

                                $tuple = [$score, $sd, $td];

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

            $res = [];

            $si = count($source);
            $ti = count($target);

            while (true) {
                list($c, $sd, $td) = $m[$si][$ti];

                if ($sd == 0 && $td == 0) {
                    break;
                }

                $res[] = [[$si - $sd, $sd], [$ti - $td, $td]];

                $si -= $sd;
                $ti -= $td;
            }

            return array_reverse($res);
        }

        function translateSegment($segment, $source_lang, $target_lang) {
            $config = Aligner::getConfig();

            $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
            $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
            $engineRecord->type = 'MT';

            $engine = new \Engines_GoogleTranslate($engineRecord);

            $res = $engine->get([
                'source' => $source_lang,
                'target' => $target_lang,
                'segment' => $segment
            ]);

            return $res['translation'];
        }

        // Pre-translate segments from source_lang to target_lang
        function translateSegments($segments, $source_lang, $target_lang) {
            $result = [];

            foreach ($segments as $segment) {
                $segment['clean'] = translateSegment($segment['clean'], $source_lang, $target_lang);
                $result[] = $segment;
            }

            return $result;
        }


        // Variant on Church and Gale algorithm with Levenshtein distance
        $source_translated = translateSegments($source, $source_lang, $target_lang);

        $indexes = align($source_translated, $target);

        $alignment = [];
        foreach ($indexes as $index) {  // Every index contains [[offset, length], [offset, length]] of the source/target slice
            $si = $index[0][0];
            $ti = $index[1][0];

            $sd = $index[0][1];
            $td = $index[1][1];

            $row = [
                'source' => mergeSegments(array_slice($source, $si, $sd)),
                'target' => mergeSegments(array_slice($target, $ti, $td))
            ];

            $alignment[] = $row;
        }

        return $alignment;
    }

    /**
     *  Alignment based on fixed window size for search and editing distance for scoring
     *
     * @param $source
     * @param $target
     * @param $source_lang
     * @param $target_lang
     * @return array
     */
    protected function _alignSegmentsV3C($source, $target, $source_lang, $target_lang) {

        function long_levenshtein($str1, $str2, $costIns, $costRep, $costDel) {

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

        // Simple merge, for 1-N matches
        function mergeSegments($segments) {
            if (count($segments) == 1) {
                return reset($segments);  // Here I'm not sure if array starts with index 0
            } else {
                return array_reduce($segments, function ($carry, $item) {
                    $carry['raw'] = trim($carry['raw'] . ' ' . $item['raw']);
                    $carry['clean'] = trim($carry['clean'] . ' ' . $item['clean']);
                    $carry['words'] += $item['words'];

                    return $carry;
                }, ['raw' => '', 'clean' => '', 'words' => 0]);
            }
        }

        // IMPROVED: we discard common words and perform levenshtein only on diff parts of string
        function eval_sents($sources, $targets) {
            $costIns = 1; $costRep = 1; $costDel = 1;

            $ss = mergeSegments($sources)['clean'];
            $ts = mergeSegments($targets)['clean'];

            // Lowercase to better comparison
            $ss = strtolower($ss);
            $ts = strtolower($ts);

            // Get all words, this is needed to remove common words and calculate distance only on different words (type, plurals, ...)
            $delimiters = '/\s|\t|\n|\r|\?|\"|\“|\”|\.|\,|\;|\:|\!|\(|\)|\{|\}|\`|\’|\\\|\/|\||\'|\+|\-|\_/';

            $ss = preg_split($delimiters, $ss, null, PREG_SPLIT_NO_EMPTY);
            $ts = preg_split($delimiters, $ts, null, PREG_SPLIT_NO_EMPTY);

            $commonWords = array_intersect($ss, $ts);

            $ss = array_diff($ss, $commonWords);
            $ts = array_diff($ts, $commonWords);

            // Put back to string to allow calculations (without spaces, so we optimize characters)
            $ss = implode('', $ss);
            $ts = implode('', $ts);

            if (strlen($ss) == 0 || strlen($ts) == 0) {  // Check if we can return immediately the upper bound
                return max(strlen($ss), strlen($ts));
            } else if (strlen($ss) < 255 && strlen($ts) < 255) {  // Check if we can use the efficient standard implementation
                return levenshtein($ss, $ts, $costIns, $costRep, $costDel);
            } else {
                return long_levenshtein($ss, $ts, $costIns, $costRep, $costDel);
            }
        }

        function buildScores($source, $target) {

            // $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];  // Penality for merge and holes
            $beadCosts = ['1-1' => 0, '2-1' => 100, '1-2' => 100, '0-1' => 50, '1-0' => 50];  // Penality for merge and holes

            $m = [];
            foreach (range(0, count($source)) as $si) {
                foreach (range(0, count($target)) as $ti) {
                    if ($si == 0 && $ti == 0) {
                        $m[0][0] = [0, 0, 0];
                    } else {

                        $value = null;

                        foreach ($beadCosts as $pair => $beadCost) {
                            $sd = intval(substr($pair, 0, 1));
                            $td = intval(substr($pair, -1, 1));

                            if ($si - $sd >= 0 && $ti - $td >= 0) {

                                $sources = array_slice($source, $si-$sd, $sd);
                                $targets = array_slice($target, $ti-$td, $td);

                                $score = $m[$si-$sd][$ti-$td][0] + eval_sents($sources, $targets) + $beadCost;

                                $tuple = [$score, $sd, $td];

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

        function extractPath($source, $target, $scores) {
            $res = [];

            $si = count($source);
            $ti = count($target);

            while (true) {
                list($c, $sd, $td) = $scores[$si][$ti];

                if ($sd == 0 && $td == 0) {
                    break;
                }

                $res[] = [[$si - $sd, $sd], [$ti - $td, $td]];

                $si -= $sd;
                $ti -= $td;
            }

            return array_reverse($res);
        }

        function findAbsoluteMatches($source, $target) {
            // Try to find a 100% match (equal strings) and split align work in multiple sub-works (divide et impera)
            $delimiters = '/\s|\t|\n|\r|\?|\"|\“|\”|\.|\,|\;|\:|\!|\(|\)|\{|\}|\`|\’|\\\|\/|\||\'|\+|\-|\_/';

            $source = array_map(function ($item) use ($delimiters) {
                $text = strtolower($item['clean']);
                $elements = preg_split($delimiters, $text, null, PREG_SPLIT_NO_EMPTY);
                $text = implode('', $elements);
                return $text;
            }, $source);

            $target = array_map(function ($item) use ($delimiters) {
                $text = strtolower($item['clean']);
                $elements = preg_split($delimiters, $text, null, PREG_SPLIT_NO_EMPTY);
                $text = implode('', $elements);
                return $text;
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

        function align($source, $target) {

            $matches = findAbsoluteMatches($source, $target);

            // No exact match has been found, we need to align entire document
            if (empty($matches)) {
                $scores = buildScores($source, $target);
                $alignment = extractPath($source, $target, $scores);

                return $alignment;
            } else {
                // Perform alignment on all sub-array, then merge them
                $alignment = [];
                $lastMatch = [0, 0];

                foreach ($matches as $match) {

                    $subSource = array_slice($source, $lastMatch[0], $match[0] - $lastMatch[0]);
                    $subTarget = array_slice($target, $lastMatch[1], $match[1] - $lastMatch[1]);

                    $scores = buildScores($subSource, $subTarget);
                    $subAlignment = extractPath($subSource, $subTarget, $scores);

                    // Adjust offset
                    $subAlignment = array_map(function ($item) use ($lastMatch) {
                        $item[0][0] += $lastMatch[0];
                        $item[1][0] += $lastMatch[1];
                        return $item;
                    }, $subAlignment);

                    $alignment = array_merge($alignment, $subAlignment);  // Add alignments for sub-array
                    $alignment[] = [[$match[0], 1], [$match[1], 1]];  // Add alignment for exact match

                    // Update lastMatch to skip current match
                    $lastMatch[0] = $match[0] + 1;
                    $lastMatch[1] = $match[1] + 1;
                }

                // Align last part of documents, after last exact match
                $subSource = array_slice($source, $lastMatch[0]);
                $subTarget = array_slice($target, $lastMatch[1]);

                $scores = buildScores($subSource, $subTarget);
                $subAlignment = extractPath($subSource, $subTarget, $scores);

                // Adjust offset
                $subAlignment = array_map(function ($item) use ($lastMatch) {
                    $item[0][0] += $lastMatch[0];
                    $item[1][0] += $lastMatch[1];
                    return $item;
                }, $subAlignment);

                $alignment = array_merge($alignment, $subAlignment);  // Add alignments for sub-array

                return $alignment;
            }
        }

        function translateSegment($segment, $source_lang, $target_lang) {
            $config = Aligner::getConfig();

            $engineRecord = \EnginesModel_GoogleTranslateStruct::getStruct();
            $engineRecord->extra_parameters['client_secret'] = $config['GOOGLE_API_KEY'];
            $engineRecord->type = 'MT';

            $engine = new \Engines_GoogleTranslate($engineRecord);

            $res = $engine->get([
                'source' => $source_lang,
                'target' => $target_lang,
                'segment' => $segment
            ]);

            return $res['translation'];
        }

        // Pre-translate segments from source_lang to target_lang
        function translateSegments($segments, $source_lang, $target_lang) {
            $result = [];

            foreach ($segments as $segment) {
                $segment['clean'] = translateSegment($segment['clean'], $source_lang, $target_lang);
                $result[] = $segment;
            }

            return $result;
        }


        // Variant on Church and Gale algorithm with Levenshtein distance
        $source_translated = translateSegments($source, $source_lang, $target_lang);

        $indexes = align($source_translated, $target);

        $alignment = [];
        foreach ($indexes as $index) {  // Every index contains [[offset, length], [offset, length]] of the source/target slice
            $si = $index[0][0];
            $ti = $index[1][0];

            $sd = $index[0][1];
            $td = $index[1][1];

            $row = [
                'source' => mergeSegments(array_slice($source, $si, $sd)),
                'target' => mergeSegments(array_slice($target, $ti, $td))
            ];

            $alignment[] = $row;
        }

        return $alignment;
    }

    /**
     * Temp version, remove if unused. It takes care of Tags
     *
     * @param $source
     * @param $target
     * @return array
     */
    protected function _alignSegmentsVXXX($source, $target) {

        // Utility functions for algorithm
        function tagsInSegment($segment) {
            $allMatches = [];

            $tagsRegex = [
                '|<(g\s*id=["\']+.*?["\']+\s*[^<>]*?)>|si',
                '|<(x .*?/?)>|si',
                '#<(bx[ ]{0,}/?|bx .*?/?)>#si',
                '#<(ex[ ]{0,}/?|ex .*?/?)>#si',
                '|<(bpt\s*.*?)>|si',
                '|<(ept\s*.*?)>|si',
                '|<(ph .*?)>|si',
                '|<(it .*?)>|si',
                '|<(mrk\s*.*?)>|si'
            ];

            foreach ($tagsRegex as $regex) {
                preg_match_all($regex, $segment['raw'], $matches);

                if (!empty($matches)) {
                    $allMatches = array_merge($allMatches, $matches[0]); // $matches[0] contains all raw matches
                }
            }

            return $allMatches;
        }

        function charactersInSegment($segment) {
            return strlen(str_replace(' ', '', $segment['clean']));
        }


        // Expand meta-info for source and target segments
        $source = array_map(function ($item) {
            $item['tags'] = tagsInSegment($item);
            $item['chars'] = charactersInSegment($item);
            return $item;
        }, $source);

        $target = array_map(function ($item) {
            $item['tags'] = tagsInSegment($item);
            $item['chars'] = charactersInSegment($item);
            return $item;
        }, $target);


        // Alignment
        $alignment = array();

        $source_length = count($source);
        $target_length = count($target);

        $index = 0;
        while (true) {
            if ($index < $source_length && $index < $target_length) {
                $row = [
                    'source' => $source[$index],
                    'target' => $target[$index]
                ];

                $alignment[] = $row;
            } else if ($index < $source_length) {
                $row = [
                    'source' => $source[$index],
                    'target' => null
                ];

                $alignment[] = $row;
            } else if ($index < $target_length) {
                $row = [
                    'source' => null,
                    'target' => $target[$index]
                ];

                $alignment[] = $row;
            } else {
                break;
            }

            $index++;
        }

        return $alignment;
    }
}
