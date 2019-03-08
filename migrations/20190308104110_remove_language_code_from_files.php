<?php

class RemoveLanguageCodeFromFiles extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE files DROP COLUMN language_code;";


    public $sql_down = "ALTER TABLE files ADD COLUMN language_code varchar(45) NOT NULL;";
}