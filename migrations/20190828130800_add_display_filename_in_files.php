<?php

use Phinx\Migration\AbstractMigration;

class AddDisplayFilenameInFiles extends AbstractMatecatMigration
{

    public $sql_up = "ALTER TABLE `files` ADD COLUMN `display_filename` varchar(255) DEFAULT NULL;
                      UPDATE `files` SET `display_filename` = `filename` WHERE `display_filename` IS NULL;";

    public $sql_down = "ALTER TABLE `files` DROP COLUMN `display_filename`;";

}
