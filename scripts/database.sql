
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

CREATE TABLE `user_tokens` (
    `id` int(11) NOT NULL,
    `user` varchar(32) NOT NULL,
    `token` varchar(172) NOT NULL COMMENT 'base64 encoding of user token',
    `expires_at` datetime NOT NULL
);
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);
