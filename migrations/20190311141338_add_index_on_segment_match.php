<?php

class AddIndexOnSegmentMatch extends AbstractMatecatMigration
{
    public $sql_up = "ALTER TABLE segments_match ADD KEY `id_job_type` (`id_job`, `type` );";

    public $sql_down = "ALTER TABLE segments_match DROP KEY `id_job_type`;";
}
