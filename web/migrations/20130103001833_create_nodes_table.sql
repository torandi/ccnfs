CREATE TABLE IF NOT EXISTS `nodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
	`computer_id` int(11) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `type` enum('dir','file') NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
	KEY `computer_id` (`computer_id`),
  KEY `parent` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
