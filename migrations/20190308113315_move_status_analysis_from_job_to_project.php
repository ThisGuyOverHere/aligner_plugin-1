<?php

class MoveStatusAnalysisFromJobToProject extends AbstractMatecatMigration
{
    public $sql_up = [
            "ALTER TABLE projects ADD COLUMN `status_analysis` enum('not_started', 'started', 'segments_created', 'fetching', 'translating', 'aligning', 'merging', 'complete', 'words_limit_exceeded', 'error') NOT NULL DEFAULT 'not_started';",
            "UPDATE projects as p SET status_analysis = (SELECT status_analysis FROM jobs WHERE id_project = p.id); ",
            "ALTER TABLE jobs DROP COLUMN status_analysis;"
    ];


    public $sql_down = [
            "ALTER TABLE jobs ADD COLUMN `status_analysis` enum('not_started', 'started', 'segments_created', 'fetching', 'translating', 'aligning', 'merging', 'complete', 'words_limit_exceeded', 'error') NOT 
NULL DEFAULT 'not_started';",
            "UPDATE jobs as j SET status_analysis = (SELECT status_analysis FROM projects WHERE id = j.id_project); ",
            "ALTER TABLE projects DROP COLUMN status_analysis;"
    ];
}