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

    private function getJsonCachePath( $prefix, $lang, $jsonPath ){
        return self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $prefix . self::OBJECTS_SAFE_DELIMITER . $lang . '/json/' . $this->getTheLastPartOfKey( $jsonPath ). ".json";
    }

    private function getJsonCacheFolder( $prefix, $lang ){
        return self::CACHE_PACKAGE_FOLDER . DIRECTORY_SEPARATOR . $prefix . self::OBJECTS_SAFE_DELIMITER . $lang . '/json/';
    }


    public function getJsonCachePackage( $prefix, $lang ) {

        $s3Client = static::getStaticS3Client();

        $prefix = $this->getJsonCacheFolder($prefix, $lang);

        $items = $s3Client->getItemsInABucket( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'prefix' => $prefix ] );
        $item = ( isset( $items[ 0 ] ) ) ? $items[ 0 ] : null;
        if( isset( $item ) ){
            $content = $s3Client->openItem( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'key' => $item ] );
        }

        return $content;
    }

    public function makeJsonCachePackage( $prefix, $lang, $originalPath = false, $jsonPath ) {

        // get the prefix
        $file   = $this->getJsonCachePath($prefix, $lang, $jsonPath);
        $valid  = $this->s3Client->hasItem( [ 'bucket' => static::$FILES_STORAGE_BUCKET, 'key' => $file ] );

        if ( !$valid ) {
            $this->tryToUploadAFile( static::$FILES_STORAGE_BUCKET, $file, $originalPath );
            //$this->uploadJson( $prefix, $jsonPath, static::$FILES_STORAGE_BUCKET, $originalPath );
            unlink( $originalPath );
        }

        return true;
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