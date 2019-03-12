<?php


class RemoveDueDateProject extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE projects DROP COLUMN due_date;";

    public $sql_down = "ALTER TABLE projects ADD COLUMN `due_date` DATE DEFAULT NULL;";
}