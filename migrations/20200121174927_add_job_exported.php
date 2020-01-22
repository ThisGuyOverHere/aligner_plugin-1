<?php

class AddJobExported extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE jobs ADD COLUMN exported int(2) NOT NULL DEFAULT 0;";

    public $sql_down = "ALTER TABLE jobs DROP COLUMN exported;";
}