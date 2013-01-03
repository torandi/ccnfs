ALTER TABLE `nodes` DROP FOREIGN KEY `nodes_ibfk_2` ,
ADD FOREIGN KEY ( `parent` ) REFERENCES `ccnfs`.`nodes` (
`id`
) ON DELETE CASCADE ON UPDATE CASCADE ;
