
CREATE TABLE `amount` (
    `amount` int(11) NOT NULL
);

CREATE TABLE `goals` (
    `id` int(11) NOT NULL,
    `user` varchar(32) NOT NULL,
    `name` varchar(64) NOT NULL,
    `total` int(11) NOT NULL,
    `amount` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_added` datetime NOT NULL DEFAULT current_timestamp()
);
ALTER TABLE `goals` ADD PRIMARY KEY (`id`);

CREATE TABLE `transactions` (
    `id` int(11) NOT NULL,
    `user` varchar(32) NOT NULL,
    `amount` int(11) NOT NULL,
    `description` varchar(64) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_added` datetime NOT NULL DEFAULT current_timestamp()
);
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date_added` (`date_added`);

ALTER TABLE `transactions` ADD COLUMN `goal_id` int(11) NULL DEFAULT NULL;

CREATE TABLE `user_tokens` (
    `id` int(11) NOT NULL,
    `user` varchar(32) NOT NULL,
    `token` varchar(172) NOT NULL COMMENT 'base64 encoding of user token',
    `expires_at` datetime NOT NULL
);
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

CREATE TABLE `recurring_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user` varchar(32) NOT NULL,
    `amount` int(11) NOT NULL,
    `description` varchar(64) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `start_month` varchar(7) NOT NULL,
    `end_month` varchar(7) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_active` (`user`, `active`)
);

CREATE TABLE `recurring_processed` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user` varchar(32) NOT NULL,
    `month` varchar(7) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_month` (`user`, `month`)
);
