
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
    `month` varchar(7) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `month` (`month`)
);

CREATE TABLE `upcoming_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user` varchar(32) NOT NULL,
    `amount` int(11) NOT NULL,
    `description` varchar(64) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `target_month` varchar(7) NOT NULL,
    `processed` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `user_active` (`user`, `active`)
);

CREATE TABLE `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    `color` varchar(7) NOT NULL DEFAULT '#c17f52',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `active` (`active`)
);

-- Separate table (rather than a column on `transactions`) so a transaction
-- could reference multiple categories in the future without a schema change.
-- The UNIQUE key enforces today's one-category-per-transaction rule at the
-- DB level; drop it if/when multiple categories per transaction are supported
-- (loadTransactionsStartEndDate's LEFT JOIN would also need to change then).
CREATE TABLE `transaction_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_id` (`transaction_id`),
    KEY `category_id` (`category_id`)
);

ALTER TABLE `recurring_transactions` ADD COLUMN `category_id` int(11) NULL DEFAULT NULL;
ALTER TABLE `upcoming_transactions` ADD COLUMN `category_id` int(11) NULL DEFAULT NULL;
