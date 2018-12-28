<?php

class HiddenInMatches extends AbstractMatecatMigration
{
    public $sql_up = [
        "ALTER TABLE `segments_match` ADD COLUMN hidden tinyint(4) not null DEFAULT 0, algorithm=INPLACE, lock=NONE"
    ];

    public $sql_down = [
        "ALTER TABLE `segments_match` DROP COLUMN hidden, algorithm=INPLACE, lock=NONE"
    ];
}