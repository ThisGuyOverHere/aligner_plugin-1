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
    /**
     * @var Client
     */
    private $s3Client;

    /**
     * @var Client
     */
    private static $CLIENT;

    /**
     * @var string
     */
    private static $FILES_STORAGE_BUCKET;

    /**
     * S3FilesStorage constructor.
     *
     * Create the bucket if not exists
     *
     * @throws \Exception
     */
    public function __construct() {
        $this->s3Client = self::getStaticS3Client();
        self::setFilesStorageBucket();
    }

    /**
     * This static method gives
     * an access to Client instance
     * to all static methods like moveFileFromUploadSessionToQueuePath()
     *
     * @return Client
     * @throws \Predis\Connection\ConnectionException
     * @throws \ReflectionException
     */
    public static function getStaticS3Client() {

        if ( empty( self::$CLIENT ) ) {
            // init the S3Client
            $awsVersion = \INIT::$AWS_VERSION;
            $awsRegion  = \INIT::$AWS_REGION;

            $config = [
                'version' => $awsVersion,
                'region'  => $awsRegion,
            ];

            if ( null !== \INIT::$AWS_ACCESS_KEY_ID and null !== \INIT::$AWS_SECRET_KEY ) {
                $config[ 'credentials' ] = [
                    'key'    => \INIT::$AWS_ACCESS_KEY_ID,
                    'secret' => \INIT::$AWS_SECRET_KEY,
                ];
            }

            self::$CLIENT = new Client( $config );

            // add caching
            if ( \INIT::$AWS_CACHING == true ) {
                $redis = new \RedisHandler();
                self::$CLIENT->addCache( new RedisCache( $redis->getConnection() ) );
            }

            // disable SSL verify from configuration
            if ( false === \INIT::$AWS_SSL_VERIFY ) {
                self::$CLIENT->disableSslVerify();
            }
        }

        self::setFilesStorageBucket();

        return self::$CLIENT;
    }

    /**
     * set $FILES_STORAGE_BUCKET
     */
    private static function setFilesStorageBucket() {
        if ( null === \INIT::$AWS_STORAGE_BASE_BUCKET ) {
            throw new \DomainException( '$AWS_STORAGE_BASE_BUCKET param is missing in INIT.php.' );
        }

        static::$FILES_STORAGE_BUCKET = \INIT::$AWS_STORAGE_BASE_BUCKET;
    }

    public function makeJsonCachePackage( $prefix, $lang, $originalPath = false, $jsonPath ) {

        // get the prefix
        $file   = self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $prefix . self::OBJECTS_SAFE_DELIMITER . $lang . '/json/' . $this->getTheLastPartOfKey( $jsonPath );
        $valid  = $this->s3Client->hasItem( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'key' => $file ] );

        if ( !$valid ) {
            $this->tryToUploadAFile( static::$FILES_STORAGE_BUCKET, $file, $originalPath );
            //$this->uploadJson( $prefix, $jsonPath, static::$FILES_STORAGE_BUCKET, $originalPath );
            unlink( $originalPath );
        }

        return true;
    }

    /**
     * @param $hash
     * @param $lang
     *
     * @return string
     */
    private function getCachePackageHashFolder( $hash, $lang ) {
        $hashTree = self::composeCachePath( $hash );

        return self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $hashTree[ 'firstLevel' ] . DIRECTORY_SEPARATOR . $hashTree[ 'secondLevel' ] . DIRECTORY_SEPARATOR . $hashTree[ 'thirdLevel' ] .
            self::OBJECTS_SAFE_DELIMITER . $lang;
    }

    /**
     * @param      $prefix
     * @param      $xliffPath
     * @param      $bucketName
     * @param bool $originalPath
     *
     * @return string
     */
    private function uploadJson( $prefix, $jsonPath, $bucketName, $originalPath ) {
        $raw_file_path   = explode( DIRECTORY_SEPARATOR, $originalPath );
        $file_name       = array_pop( $raw_file_path );
        $origDestination = self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . $file_name;

        $this->tryToUploadAFile( $bucketName, $origDestination, $originalPath );
    }

    /**
     * @param $bucketName
     * @param $destination
     * @param $origPath
     *
     * @return bool
     */
    private function tryToUploadAFile( $bucketName, $destination, $origPath ) {
        try {
            $this->s3Client->uploadItem( [
                'bucket' => $bucketName,
                'key'    => $destination,
                'source' => $origPath
            ] );

            \Log::doJsonLog( 'Successfully uploaded file ' . $destination . ' into ' . $bucketName . ' bucket.' );
        } catch ( \Exception $e ) {
            \Log::doJsonLog( 'Error in uploading a file ' . $destination . ' into ' . $bucketName . ' bucket. ERROR: ' . $e->getMessage() );

            return false;
        }
    }
}