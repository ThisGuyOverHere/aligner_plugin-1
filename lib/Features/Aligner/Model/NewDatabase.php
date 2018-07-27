<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 03/07/2018
 * Time: 17:57
 */

namespace Features\Aligner\Model ;

use Database;

class NewDatabase extends Database {
    /**
     * Unique instance of the class (singleton design pattern)
     * @var Database $instance
     */
    private static $instance;

    // Connection variables
    private $server = ""; //database server
    private $user = ""; //database login name
    private $password = ""; //database login password
    private $database = ""; //database name

    /**
     * Instantiate the database (singleton design pattern)
     * @param string $server
     * @param string $user
     * @param string $password
     * @param string $database
     */
    private function __construct($server=null, $user=null, $password=null, $database=null) {

        // Check that the variables are not empty
        if ($server == null || $user == null || $database == null) {
            throw new InvalidArgumentException("Database information must be passed in when the object is first created.");
        }

        // Set fields
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    /**s
     * @Override
     * {@inheritdoc}
     */
    public static function obtain($server=null, $user=null, $password=null, $database=null) {
        if (!static::$instance  ||  $server != null  &&  $user != null  &&  $password != null  &&  $database != null) {
            static::$instance = new NewDatabase($server, $user, $password, $database);
        }
        return static::$instance;
    }

}