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

    private $tmxFilePath;


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
     * @return \SplTempFileObject $tmpFile
     *
     * @throws \Exception
     */
    public function exportJobAsTMX( $jid, $jPassword, $sourceLang, $targetLang ) {

        $this->tmxFilePath = tempnam("/tmp", "TMX_");
        $tmxHandler = new \SplFileObject($this->tmxFilePath, "w");

        $tmxHandler->fwrite( '<?xml version="1.0" encoding="UTF-8"?>
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
        $matches    = $segmentDao->getTranslationsForTMXExport( $jid );

        foreach ( $matches as $k => $row ) {

            if ( empty( $row[ 'source' ] ) || empty( $row[ 'target' ] ) ) {
                continue;
            }

            $tmx = '
    <tu datatype="plaintext" srclang="' . $sourceLang . '">
        <tuv xml:lang="' . $sourceLang . '">
            <seg>' . \CatUtils::rawxliff2rawview( $row[ 'source' ] ) . '</seg>
        </tuv>';

            $tmx .= '
        <tuv xml:lang="' . $targetLang . '">
            <seg>' . \CatUtils::rawxliff2rawview( $row[ 'target' ] ) . '</seg>
        </tuv>';


            $tmx .= '
    </tu>
';

            $tmxHandler->fwrite( $tmx );

        }

        $tmxHandler->fwrite( "
    </body>
</tmx>" );

        return $this->tmxFilePath;

    }

    public function importTMXInTM($tm_key){

        $this->mymemory_engine->import(
                $this->tmxFilePath,
                $tm_key
        );
    }

    public function downloadTMX($project_name){
        $buffer = ob_get_contents();
        ob_get_clean();
        ob_start( "ob_gzhandler" );  // compress page before sending
        header( "Content-Type: application/force-download" );
        header( "Content-Type: application/octet-stream" );
        header( "Content-Type: application/download" );

        // Enclose file name in double quotes in order to avoid duplicate header error.
        // Reference https://github.com/prior/prawnto/pull/16
        header( "Content-Disposition: attachment; filename=\"$project_name\".tmx" );
        header( "Expires: 0" );
        header( "Connection: close" );

        print file_get_contents($this->tmxFilePath);

        exit;
    }




}