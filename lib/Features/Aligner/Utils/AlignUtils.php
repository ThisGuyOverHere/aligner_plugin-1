<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 12/09/18
 * Time: 13:06
 */

namespace Features\Aligner\Utils;

class AlignUtils
{
    /**
     *
     * Code almost cloned from CatUtils::placehold_xliff_tags()
     *
     * @param $segment
     * @param $lang
     * @return null|string|string[]
     */
    public static function _cleanSegment($segment, $lang) {

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
    public static function _countWordsInSegment($segment, $lang) {
        $wordCount = \CatUtils::segment_raw_wordcount( $segment, $lang );

        return $wordCount;
    }

    public static function _getNewOrderValue($first_order, $next_order){
        if($first_order && $next_order){
            return $first_order + ( $next_order - $first_order )/2;
        } else if(!$first_order && !$next_order) {
            return null;
        } else {
            $order = ($first_order == null) ? $next_order/2 : $first_order + Constants::DISTANCE_INT_BETWEEN_MATCHES;
            return $order;
        }
    }

    public static function _mark_xliff_tags($segment) {

        //remove not existent </x> tags
        $segment = preg_replace('|(</x>)|si', "", $segment);

        //$segment=preg_replace('|<(g\s*.*?)>|si', LTPLACEHOLDER."$1".GTPLACEHOLDER,$segment);
        $segment = preg_replace('|<(g\s*id=["\']+.*?["\']+\s*[^<>]*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);

        $segment = preg_replace('|<(/g)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);

        $segment = preg_replace('|<(x .*?/?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('#<(bx[ ]{0,}/?|bx .*?/?)>#si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('#<(ex[ ]{0,}/?|ex .*?/?)>#si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(bpt\s*.*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(/bpt)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(ept\s*.*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(/ept)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(ph .*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(/ph)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(it .*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(/it)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(mrk\s*.*?)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);
        $segment = preg_replace('|<(/mrk)>|si', Constants::LTPLACEHOLDER . "$1" . Constants::GTPLACEHOLDER, $segment);

        return $segment;

    }

    public static function _restore_xliff_tags($segment) {

        $segment = str_replace(Constants::LTPLACEHOLDER, "<", $segment);
        $segment = str_replace(Constants::GTPLACEHOLDER, ">", $segment);
        return $segment;

    }

    public static function _parseArrayIntegers(array &$array){
        foreach ($array as $key => $value){
            if(is_numeric($array[$key])){
                $array[$key] = (int) $value;
            }
        }
    }

    public static function _getObjectVariables($object){
        if(!is_object($object)){
            return new \Exception("This function accepts Object-type variables only");
        }
        return array_keys(get_object_vars($object));
    }

    public static function _array_union($x, $y){
        $aunion=  array_merge(
            array_intersect($x, $y),
            array_diff($x, $y),
            array_diff($y, $x)
        );
        return $aunion;
    }
}