<?php

class RemoveRawContentAndContentHash extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE segments DROP COLUMN content_raw, DROP COLUMN content_hash;";

    public $sql_down = "ALTER TABLE segments ADD COLUMN content_raw TEXT, ADD COLUMN content_hash VARCHAR(45);";
}
