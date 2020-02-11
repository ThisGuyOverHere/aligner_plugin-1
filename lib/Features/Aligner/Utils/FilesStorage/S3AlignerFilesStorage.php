<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 02/10/2019
 * Time: 15:32
 */

namespace Features\Aligner\Utils\FilesStorage;

use Exception;
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

            try {

                $this->s3Client->uploadItem( [
                        'bucket' => static::$FILES_STORAGE_BUCKET,
                        'key'    => $file,
                        'source' => $originalPath
                ] );

                \Log::doJsonLog( 'Successfully uploaded file ' . $file . ' into ' . static::$FILES_STORAGE_BUCKET . ' bucket.' );

            } catch ( \Exception $e ) {

                \Log::doJsonLog( 'Error in uploading a file ' . $file . ' into ' . static::$FILES_STORAGE_BUCKET . ' bucket. ERROR: ' . $e->getMessage() );
                return false;
            }

            //$this->uploadJson( $prefix, $jsonPath, static::$FILES_STORAGE_BUCKET, $originalPath );
            unlink( $originalPath );

        }

        return true;
    }
}