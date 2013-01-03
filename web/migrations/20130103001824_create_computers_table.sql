CREATE TABLE IF NOT EXISTS `computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
