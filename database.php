<?php

/*
CREATE TABLE `amount` ( `amount` INT NOT NULL );
CREATE TABLE `transactions` ( `id` INT NOT NULL AUTO_INCREMENT , `user` VARCHAR(32) NOT NULL , `amount` INT NOT NULL , `description` VARCHAR(64) NOT NULL , `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`))
CREATE TABLE `goals` ( `id` INT NOT NULL AUTO_INCREMENT , `user` VARCHAR(32) NOT NULL , `name` VARCHAR(64) NOT NULL , `total` INT NOT NULL , `amount` INT NOT NULL , `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`))
ALTER TABLE `transactions` ADD UNIQUE `date_added` (`date_added`);
ALTER TABLE `goals` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `amount`;
ALTER TABLE `transactions` ADD `active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `description`;
CREATE TABLE `user_tokens` ( `id` INT NOT NULL AUTO_INCREMENT , `user` VARCHAR(32) NOT NULL , `token` VARCHAR(172) NOT NULL COMMENT 'base64 encoding of user token' , PRIMARY KEY (`id`));
ALTER TABLE `user_tokens` ADD UNIQUE `unique_user` (`user`);
*/

?>