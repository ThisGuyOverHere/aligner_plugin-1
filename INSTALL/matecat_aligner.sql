CREATE DATABASE matecat_aligner;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `password` varchar(45) DEFAULT NULL,
  `id_customer` int(11) DEFAULT  NULL,
  `name` varchar(200) DEFAULT 'project',
  `create_date` datetime NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_analysis` enum('not_ready_to_analisis', 'ready_to_analisis', 'in_analisis', 'empty', 'done') NOT NULL DEFAULT 'not_ready_to_analisis',
  `remote_ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_customer` (`id_customer`),
  KEY `status_analysis` (`status_analysis`),
  KEY `remote_ip_address` (`remote_ip_address`),
  KEY `name` (`name`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `password` varchar(45) NOT NULL,
  `id_project` int(11) NOT NULL,
  `source` varchar(45) DEFAULT NULL,
  `target` varchar(45) DEFAULT NULL,
  `create_date` datetime NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active', 'archived', 'cancelled') NOT NULL DEFAULT 'active',
  `subject` varchar(100) DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `primary_id_pass` (`id`,`password`),
  KEY `id_project` (`id_project`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `password` (`password`),
  KEY `source` (`source`),
  KEY `target` (`target`),
  KEY `status_idx` (`status`),
  KEY `create_date_idx` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_project` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `type` enum('source', 'target') NOT NULL,
  `language_code` varchar(45) NOT NULL,
  `mime_type` varchar(45) DEFAULT NULL,
  `sha1_original_file` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_project` (`id_project`),
  KEY `sha1` (`sha1_original_file`) USING HASH,
  KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `segments`;
CREATE TABLE `segments` (
  `seq` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_job` bigint(20) NOT NULL,
  `id_file` bigint(20) NOT NULL,
  `internal_id` varchar(100) DEFAULT NULL,
  `type` enum('target', 'source') NOT NULL,
  `xliff_ext_prec_tags` text,
  `xliff_mrk_ext_prec_tags` text,
  `content` text,
  `content_hash` varchar(45) NOT NULL,
  `xliff_mrk_ext_succ_tags` text,
  `xliff_ext_succ_tags` text,
  `language_code` varchar(45) DEFAULT NULL,
  `raw_word_count` double(20,2) DEFAULT NULL,
  `create_date` datetime NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `primary_seq_job` (`seq`,`id_job`),
  KEY `id_job` (`id_job`) USING BTREE,
  KEY `internal_id` (`internal_id`) USING BTREE,
  KEY `segment_hash` (`segment_hash`) USING HASH COMMENT 'MD5 hash of segment content'
  KEY `create_date_idx` (`create_date`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `segments_match`;
CREATE TABLE `segments_match` (
  `id_job` bigint(20) NOT NULL,
  `order` bigint(20) NOT NULL,
  `src_seq` bigint(20) DEFAULT NULL,
  `trg_seq` bigint(20) DEFAULT NULL,
  `next` bigint(20) DEFAULT NULL,
  `create_date` datetime NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `primary_job_order` (`id_job`,`order`),
  KEY `id_job` (`id_job`) USING BTREE,
  KEY `order` (`order`),
  KEY `src_seq` (`src_seq`),
  KEY `trg_seq` (`trg_seq`),
  KEY `create_date_idx` (`create_date`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
