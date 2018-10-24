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
            creationtool="Aligner-Matecat-Cattool"
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

    public function importTMXInTM(){


        return $this->mymemory_engine->import(
                $this->tmxFilePath,
                $this->getTMKey(),
                $this->name

        );
    }

    /**
     * Send a mail with link for direct prepared download
     *
     * @param $userMail
     * @param $userName
     * @param $userSurname
     *
     * @return Engines_Results_MyMemory_ExportResponse
     * @throws Exception
     */
    public function requestChunkTMXEmailDownload( $id_tmx, $userMail, $userName, $userSurname ){



        $engineDAO        = new \EnginesModel_EngineDAO( \Database::obtain() );
        $engineStruct     = \EnginesModel_EngineStruct::getStruct();
        $engineStruct->id = 1;

        $eng = $engineDAO->setCacheTTL( 60 * 5 )->read( $engineStruct );

        /**
         * @var $engineRecord EnginesModel_EngineStruct
         */
        $engineRecord = @$eng[0];

        $mymemory_engine = new Engines_MyMemory($engineRecord);

        $response = $mymemory_engine->emailExport(
                $this->getTMKey(),
                $this->name,
                $userMail,
                $userName,
                $userSurname,
                $id_tmx
        );

        return $response;

    }




}