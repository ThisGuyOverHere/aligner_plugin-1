<?php

class RemoveLanguageCodeFromSegments extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE segments DROP COLUMN language_code;";


    public $sql_down = "ALTER TABLE segments ADD COLUMN language_code varchar(45) NOT NULL;";
}