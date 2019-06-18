<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 17/12/2018
 * Time: 11:24
 */

require_once __DIR__.'/../../inc/Bootstrap.php';
Bootstrap::start();

$config = Features\Aligner::getConfig();

/**
 * Create migration ./vendor/bin/phinx create CreateTableFoo -c plugins/aligner/phinx.php
 * Exectute migrations ./vendor/bin/phinx migrate -c plugins/aligner/phinx.php
 * Rollback migrations ./vendor/bin/phinx rollback -c plugins/aligner/phinx.php
 * Check status ./vendor/bin/phinx status -c plugins/aligner/phinx.php
 */
return array(
        'paths' => array(
                'migrations' => __DIR__.'/migrations'
        ),
        'environments' => array(
                'default_migration_table' => 'phinxlog',
                'default_database' => 'auto',
                'auto' => array(
                        'adapter' => 'mysql',
                        'name' => $config['DB_DATABASE'],
                        'user' => $config['DB_USER'],
                        'pass' => $config['DB_PASS'],
                        'host' => $config['DB_SERVER'],
                )
        )
);