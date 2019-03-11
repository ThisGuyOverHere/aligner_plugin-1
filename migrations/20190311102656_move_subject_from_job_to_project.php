<?php

class MoveSubjectFromJobToProject extends AbstractMatecatMigration
{
    public $sql_up = [
            "ALTER TABLE jobs DROP COLUMN subject;",
            "ALTER TABLE projects Add COLUMN `subject` varchar(100) DEFAULT 'general';"
    ];

    public $sql_down = [
            "ALTER TABLE projects DROP COLUMN subject;",
            "ALTER TABLE jobs Add COLUMN `subject` varchar(100) DEFAULT 'general';"
    ];
}