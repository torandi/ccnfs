CREATE TABLE IF NOT EXISTS `command_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `command` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `computer_id` (`computer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `command_queue`
  ADD CONSTRAINT `command_queue_ibfk_1` FOREIGN KEY (`computer_id`) REFERENCES `computers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
