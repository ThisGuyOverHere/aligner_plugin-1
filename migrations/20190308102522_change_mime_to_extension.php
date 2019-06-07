<?php

class ChangeMimeToExtension extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE `files` Change `mime_type` `extension` varchar(45) DEFAULT NULL;";


    public $sql_down = "ALTER TABLE `files` Change `extension` `mime_type` varchar(45) DEFAULT NULL;";
}
