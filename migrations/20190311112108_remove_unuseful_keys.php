<?php

class RemoveUnusefulKeys extends AbstractMatecatMigration {
    public $sql_up = [
            "ALTER TABLE jobs DROP INDEX status_idx;",
            "ALTER TABLE segments_match DROP INDEX `order`;",
            "ALTER TABLE segments_match DROP INDEX `type`;"
    ];

    public $sql_down = [
            "ALTER TABLE jobs ADD INDEX `status_idx` (`status`);",
            "ALTER TABLE segments_match ADD INDEX `order` (`order`);",
            "ALTER TABLE segments_match ADD INDEX `type` (`type`);"
    ];
}