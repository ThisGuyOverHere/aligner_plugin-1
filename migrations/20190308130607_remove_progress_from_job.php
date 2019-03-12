<?php

class RemoveProgressFromJob extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE jobs DROP COLUMN progress;";

    public $sql_down = "ALTER TABLE jobs ADD COLUMN `progress` int(2) NOT NULL DEFAULT 0;";
}