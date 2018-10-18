<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 11/10/2018
 * Time: 19:56
 */

namespace Features\Aligner\Utils;

use Features\Aligner\Model\Segments_SegmentDao;

class TMSService extends \TMSService {


    /**
     * Export Job as Tmx File
     *
     * @param $jid
     * @param $sourceLang
     * @param $targetLang
     *
     * @return SplTempFileObject $tmpFile
     *
     */
    public function exportJobAsCSV( $jid, $sourceLang, $targetLang ) {

        $tmpFile = new SplTempFileObject( 15 * 1024 * 1024 /* 15MB */ );

        $csv_fields = [
                "Source: $sourceLang", "Target: $targetLang"
        ];

        $tmpFile->fputcsv( $csv_fields );

        $segmentDao = new Segments_SegmentDao();
        $result     = $segmentDao->getTranslationsForTMXExport($jid);

        $matches = [];
        foreach ( $result[ 'target' ] as $key => $target ) {
            $matches[ $key ][ 'translation' ] = $target[ 'content_raw' ];
        }

        foreach ( $result[ 'source' ] as $key => $source ) {
            $matches[ $key ][ 'segment' ] = $source[ 'content_raw' ];
        }

        foreach ( $matches as $k => $row ) {

            $row_array = [
                    $row[ 'segment' ], $row[ 'translation' ]
            ];

            $tmpFile->fputcsv( $row_array );

        }

        $tmpFile->rewind();

        return $tmpFile;

    }

    /**
     * Export Job as Tmx File
     *
     * @param          $jid
     * @param          $jPassword
     * @param          $sourceLang
     * @param          $targetLang
     *
     * @param int|null $uid
     *
     * @return SplTempFileObject $tmpFile
     *
     * @throws Exception
     */
    public function exportJobAsTMX( $jid, $jPassword, $sourceLang, $targetLang, $uid = null ) {

        $tmpFile = new SplTempFileObject( 15 * 1024 * 1024 /* 5MB */ );

        $tmpFile->fwrite( '<?xml version="1.0" encoding="UTF-8"?>
<tmx version="1.4">
    <header
            creationtool="Matecat-Cattool"
            creationtoolversion="' . \INIT::$BUILD_NUMBER . '"
	    o-tmf="Matecat"
            creationid="Matecat-Aligner"
            datatype="plaintext"
            segtype="sentence"
            adminlang="en-US"
            srclang="' . $sourceLang . '"/>
    <body>' );


        $segmentDao = new Segments_SegmentDao();
        $result     = $segmentDao->getTranslationsForTMXExport($jid);

        $matches = [];
        foreach ( $result[ 'target' ] as $key => $target ) {
            $matches[ $key ][ 'translation' ] = $target[ 'content_raw' ];
        }

        foreach ( $result[ 'source' ] as $key => $source ) {
            $matches[ $key ][ 'segment' ] = $source[ 'content_raw' ];
        }


        foreach ( $matches as $k => $row ) {

            $tmx = '
    <tu datatype="plaintext" srclang="' . $sourceLang . '">
        <tuv xml:lang="' . $sourceLang . '">
            <seg>' . CatUtils::rawxliff2rawview( $row[ 'segment' ] ) . '</seg>
        </tuv>';

                $tmx .= '
        <tuv xml:lang="' . $targetLang . '">
            <seg>' . CatUtils::rawxliff2rawview( $row[ 'translation' ] ) . '</seg>
        </tuv>';


            $tmx .= '
    </tu>
';

            $tmpFile->fwrite( $tmx );

        }

        $tmpFile->fwrite( "
    </body>
</tmx>" );

        $tmpFile->rewind();

        return $tmpFile;

    }
}