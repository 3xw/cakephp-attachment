# Dump of table atags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `atags`;

CREATE TABLE `atags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `slug_UNIQUE` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dump of table attachments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `attachments`;

CREATE TABLE `attachments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `type` varchar(45) NOT NULL,
  `subtype` varchar(45) NOT NULL,
  `size` int(11) NOT NULL,
  `md5` varchar(32) NOT NULL,
  `date` datetime DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `author` varchar(255) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `embed` text,
  `profile` varchar(45) NOT NULL DEFAULT 'default',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dump of table attachments_atags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `attachments_atags`;

CREATE TABLE `attachments_atags` (
  `attachment_id` bigint(20) NOT NULL,
  `atag_id` int(11) NOT NULL,
  PRIMARY KEY (`attachment_id`,`atag_id`),
  KEY `fk_attachments_has_atags_atags1_idx` (`atag_id`),
  KEY `fk_attachments_has_atags_attachments1_idx` (`attachment_id`),
  CONSTRAINT `fk_attachments_has_atags_atags1` FOREIGN KEY (`atag_id`) REFERENCES `atags` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_attachments_has_atags_attachments1` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
