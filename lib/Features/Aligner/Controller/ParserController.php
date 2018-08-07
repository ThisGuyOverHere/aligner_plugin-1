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
    use Features\Aligner\Model\Files_FileDao;

    class ParserController extends AlignerController
    {


        /**
         * @throws Exception
         */
        public function jobParser()
        {
            $id_job = $this->params['id_job'];

            $source_file = Files_FileDao::getByJobId($id_job, "source")[0];
            $target_file = Files_FileDao::getByJobId($id_job, "target")[0];

            $source_lang = $source_file->language_code;
            $target_lang = $target_file->language_code;

            $source_segments = $this->_file2segments($source_file, $source_lang);
            $target_segments = $this->_file2segments($target_file, $target_lang);

            $version = $this->params['version'];
            $alignment = [];
            switch ($version) {
                case 'v0':
                    $alignment = $this->_alignSegmentsV0($source_segments, $target_segments);
                    break;
                case 'v1':
                    $alignment = $this->_alignSegmentsV1($source_segments, $target_segments);
                    break;
            }

            // Format alignment for frontend test purpose
            $result = [
                'source' => [],
                'target' => []
            ];


            foreach ($alignment as $index => $row) {
                if($row['source']){
                    $source = $row['source'];
                }else{
                    $source['clean'] = null;
                    $source['row'] = null;
                }

                $source['order'] = ($index + 1) * 1000000000;
                $source['next'] = ($index + 2) * 1000000000;

                if($row['source']){
                    $target = $row['target'];
                }else{
                    $target['clean'] = null;
                    $target['row'] = null;
                }

                $target['order'] = ($index + 1) * 1000000000;
                $target['next'] = ($index + 2) * 1000000000;

                array_push($result['source'], $source);
                array_push($result['target'], $target);
            }
            unset($row);



            /*$alignment = array_map(function ($index, $item) {
                return [
                    'source' => ['content' => $item['source']['clean']],
                    'target' => ['content' => $item['target']['clean']],
                    'order' => ($index + 1) * 1000000000,
                    'next' => ($index + 2) * 1000000000
                ];
            }, array_keys($alignment), $alignment);

            $alignment[count($alignment) - 1]['next'] = null;*/

            $this->response->json($result);
        }


        /**
         * @param $file
         * @param $lang
         * @return array
         * @throws Exception
         */
        protected function _file2segments($file, $lang)
        {
            list($date, $sha1) = explode("/", $file->sha1_original_file);

            // Get file content
            try {
                $fileStorage = new \FilesStorage;
                $xliff_file = $fileStorage->getXliffFromCache($sha1, $file->language_code);
                $xliff_content = file_get_contents($xliff_file);
            } catch (Exception $e) {
                throw new Exception($file, $e->getCode(), $e);
            }

            // Parse xliff
            try {
                $parser = new \Xliff_Parser;
                $xliff = $parser->Xliff2Array($xliff_content);
            } catch (Exception $e) {
                throw new Exception($file, $e->getCode(), $e);
            }

            // Checking that parsing went well
            if (isset($xliff['parser-errors']) or !isset($xliff['files'])) {
                throw new Exception($file, -4);
            }

            // Creating the Segments
            $segments = array();

            foreach ($xliff['files'] as $xliff_file) {

                // An xliff can contains multiple files (docx has style, settings, ...) but only some with useful trans-units
                if (!array_key_exists('trans-units', $xliff_file)) {
                    continue;
                }

                foreach ($xliff_file['trans-units'] as $trans_unit) {

                    // Extract only raw-content
                    $unit_segments = array_map(function ($item) {
                        return $item['raw-content'];
                    }, $trans_unit['seg-source']);

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
        protected function _cleanSegment($segment, $lang)
        {

            //remove not existent </x> tags
            $segment = preg_replace('|(</x>)|si', "", $segment);

            //$segment=preg_replace('|<(g\s*.*?)>|si', LTPLACEHOLDER."$1".GTPLACEHOLDER,$segment);
            $segment = preg_replace('|<(g\s*id=["\']+.*?["\']+\s*[^<>]*?)>|si', "", $segment);

            $segment = preg_replace('|<(/g)>|si', "", $segment);

            $segment = preg_replace('|<(x .*?/?)>|si', "", $segment);
            $segment = preg_replace('#<(bx[ ]{0,}/?|bx .*?/?)>#si', "", $segment);
            $segment = preg_replace('#<(ex[ ]{0,}/?|ex .*?/?)>#si', "", $segment);
            $segment = preg_replace('|<(bpt\s*.*?)>|si', "", $segment);
            $segment = preg_replace('|<(/bpt)>|si', "", $segment);
            $segment = preg_replace('|<(ept\s*.*?)>|si', "", $segment);
            $segment = preg_replace('|<(/ept)>|si', "", $segment);
            $segment = preg_replace('|<(ph .*?)>|si', "", $segment);
            $segment = preg_replace('|<(/ph)>|si', "", $segment);
            $segment = preg_replace('|<(it .*?)>|si', "", $segment);
            $segment = preg_replace('|<(/it)>|si', "", $segment);
            $segment = preg_replace('|<(mrk\s*.*?)>|si', "", $segment);
            $segment = preg_replace('|<(/mrk)>|si', "", $segment);

            return $segment;
        }

        /**
         * @param $segment
         * @param $lang
         * @return float|int
         */
        protected function _countWordsInSegment($segment, $lang)
        {
            $wordCount = CatUtils::segment_raw_wordcount($segment, $lang);

            return $wordCount;
        }

        /**
         * Naive algorithm, it only puts side by side source and target
         *
         * @param $source
         * @param $target
         * @return array
         */
        protected function _alignSegmentsV0($source, $target)
        {

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
        protected function _alignSegmentsV1($source, $target)
        {

            // Utility functions for algorithm
            function sentenceLength($sentence)
            {
                return strlen(str_replace(' ', '', $sentence));
            }

            function _align($sourceLengths, $targetLengths, $mean, $variance, $beadCosts)
            {
                // Math utils functions
                function normCDF($value)
                {
                    $t = 1 / (1 + 0.2316419 * $value);

                    $probdist = 1 - 0.3989423 * exp(-$value * $value / 2) * (0.319381530 * $t - 0.356563782 * pow($t, 2) + 1.781477937 * pow($t, 3) - 1.821255978 * pow($t, 4) + 1.330274429 * pow($t, 5));

                    return $probdist;
                }

                function normLogs($value)
                {
                    try {
                        return log(1 - normCDF($value));
                    } catch (\Exception $e) {
                        return -INF;
                    }
                }

                function lengthCost($sourceLengths, $targetLengths, $mean, $variance)
                {
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
                                        $m[($si - $sd) . '-' . ($ti - $td)][0] + lengthCost(array_slice($sourceLengths, $si - $sd, $sd), array_slice($targetLengths, $ti - $td, $td), $mean, $variance) + $beadCost,
                                        $sd,
                                        $td
                                    ];

                                    // Emulate min function on tuple
                                    if ($value == null || $tuple[0] < $value[0]) {
                                        $value = $tuple;
                                    }
                                }
                            }

                            $m[$si . '-' . $ti] = $value;
                        }
                    }
                }

                $res = [];

                $si = count($sourceLengths);
                $ti = count($targetLengths);

                while (true) {
                    list($c, $sd, $td) = $m[$si . '-' . $ti];

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
            function mergeSegments($segments)
            {
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
            $mean = 1.0;
            $variance = 6.8;
            $beadCosts = ['1-1' => 0, '2-1' => 230, '1-2' => 230, '0-1' => 450, '1-0' => 450, '2-2' => 440];

            $sourceLengths = array_map(function ($item) {
                return sentenceLength($item['clean']);
            }, $source);
            $targetLengths = array_map(function ($item) {
                return sentenceLength($item['clean']);
            }, $target);

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
    }
