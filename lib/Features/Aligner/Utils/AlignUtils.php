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
            $order = ($first_order == null) ? $next_order/2 : $first_order + 1000000000;
            return $order;
        }
    }

}