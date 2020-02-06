<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 02/10/2019
 * Time: 15:32
 */

namespace Features\Aligner\Utils\FilesStorage;

use SimpleS3\Client;
use SimpleS3\Components\Cache\RedisCache;

class S3AlignerFilesStorage extends \FilesStorage\S3FilesStorage
{
    private function getJsonCachePath( $prefix, $lang, $jsonPath, $idFile ){
        return self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $idFile . DIRECTORY_SEPARATOR . $prefix . self::OBJECTS_SAFE_DELIMITER . $lang . '/json/' . $this->getTheLastPartOfKey( $jsonPath ).
                ".json";
    }

    private function getJsonCacheFolder( $prefix, $lang, $idFile ){
        return self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $idFile . DIRECTORY_SEPARATOR . $prefix . self::OBJECTS_SAFE_DELIMITER . $lang . '/json/';
    }


    public function getJsonCachePackage( $prefix, $lang, $idFile ) {

        $s3Client = static::getStaticS3Client();

        $prefix = $this->getJsonCacheFolder($prefix, $lang, $idFile);

        $items = $s3Client->getItemsInABucket( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'prefix' => $prefix ] );
        $item = ( isset( $items[ 0 ] ) ) ? $items[ 0 ] : null;
        if( isset( $item ) ){
            $content = $s3Client->openItem( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'key' => $item ] );
        }

        return $content;
    }

    public function makeJsonCachePackage( $prefix, $lang, $originalPath = false, $jsonPath, $idFile ) {

        // get the prefix
        $file   = $this->getJsonCachePath($prefix, $lang, $jsonPath, $idFile);
        $valid  = $this->s3Client->hasItem( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'key' => $file ] );

        if ( !$valid ) {
            $this->tryToUploadAFile( static::$FILES_STORAGE_BUCKET, $file, $originalPath );
            //$this->uploadJson( $prefix, $jsonPath, static::$FILES_STORAGE_BUCKET, $originalPath );
            unlink( $originalPath );
        }

        return true;
    }
}