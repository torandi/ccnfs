ALTER TABLE `nodes` ADD FOREIGN KEY ( `computer_id` ) REFERENCES `ccnfs`.`computers` ( `id`) ON DELETE CASCADE ON UPDATE CASCADE ;

ALTER TABLE `nodes` ADD FOREIGN KEY ( `parent` ) REFERENCES `ccnfs`.`computers` ( `id`) ON DELETE CASCADE ON UPDATE CASCADE ;
