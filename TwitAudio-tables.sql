/**
*
* TwitAudio tables and database structure
*
**/

CREATE TABLE IF NOT EXISTS users (
	`id` int(20) unsigned NOT NULL PRIMARY KEY,
	`user` varchar(15) NOT NULL,
	`name` varchar(20) NOT NULL,
	`avatar` varchar(100) NOT NULL,
	`bio` varchar(160) NOT NULL,
	`verified` enum('1', '0') NOT NULL,
	`access_token` varchar(100) NOT NULL,
	`access_token_secret` varchar(100) NOT NULL,
	`likes_public` enum('1', '0') NOT NULL,
	`audios_public` enum('1', '0') NOT NULL,
	`time` int(32) NOT NULL,
	`lang` char(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS audios (
	`id` char(6) NOT NULL PRIMARY KEY, -- like twitpic :3
	`user` int(20) unsigned NOT NULL, -- poster
	`audio` varchar(30) NOT NULL, -- audio file
	`reply_to` varchar(6) NOT NULL, -- if its a comment
	`description` varchar(200) NOT NULL,
	`tw_id` varchar(30) NOT NULL, -- tweet ID
	`time` int(32) NOT NULL,
	`plays` int(6) NOT NULL,
	`likes` int(6) NOT NULL, -- till 1m likes o_O that'd be great if they break it
	`duration` int(4) NOT NULL,
	FULLTEXT (`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS likes (
	`user_id` int(20) unsigned NOT NULL,
	`audio_id` char(6) NOT NULL,
	`time` int(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS plays (
	`user_ip` varchar(20) NOT NULL,
	`audio_id` char(6) NOT NULL,
	`time` int(32) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS blocks (
	`user_id` int(20) unsigned NOT NULL,
	`blocked_id` int(20) NOT NULL,
	`time` int(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS sessions (
	`user_id` int(20) unsigned NOT NULL,
	`sess_id` char(32) NOT NULL,
	`time` int(32) NOT NULL,
	`ip` varchar(32) NOT NULL,
	UNIQUE (`sess_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS following_cache (
	`user_id` int(20) unsigned NOT NULL,
	`following` int(20) unsigned NOT NULL,
	`time` int(32) unsigned NOT NULL,
	`result` enum('1', '0') NOT NULL
);

CREATE TABLE IF NOT EXISTS trends (
	`user` int(10) unsigned NOT NULL,
	`trend` varchar(365) NOT NULL,
	`time` int(32),
	KEY `time` (`time`),
	KEY `trend` (`trend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/** These are the changes made after the start **/

--31 / 10 / 2015 {mobile api implementation}
ALTER TABLE sessions ADD `sess_key` varchar(32) NOT NULL DEFAULT '';
-- 07 / 11 / 2015 {remove likes, add favorite; commit: 12a9050}
ALTER TABLE `users`
	CHANGE `likes_public` `favs_public` enum('1', '0') NOT NULL; 
ALTER TABLE `audios` CHANGE `likes` `favorites` int(6);
RENAME TABLE `likes` TO `favorites`;
-- 14 / 11 / 2015 { user's search; commit: 55bf134}
ALTER TABLE `users`
	ADD FULLTEXT (`user`, `name`, `bio`);
-- 10/1/2016 { register when audios are voice notes; commit: c72c7ed }
ALTER TABLE `audios`
	ADD  `is_voice` enum('1', '0') NOT NULL DEFAULT '1';
-- 23/1/2016 {bye bye session key}
ALTER TABLE `sessions` DROP COLUMN `sess_key`;
ALTER TABLE `sessions` ADD `is_mobile` enum('1', '0') NOT NULL DEFAULT '0';
-- 3/2/2016 { administration; commit: 6a5b76}
ALTER TABLE `users`
	ADD `status` enum('1', '0') NOT NULL DEFAULT '1'
		COMMENT '0 = banned, 1 = all correctly',
	ADD `ban_reason` text NOT NULL DEFAULT '';
ALTER TABLE `audios`
	ADD `status` enum('1', '0') NOT NULL DEFAULT '1'
		COMMENT '0 deleted by a reason, 1 = all correctly',
	ADD `delete_reason` text NOT NULL DEFAULT '';

--15/2/2016 { premium implementation }
ALTER TABLE `users`
	ADD `upload_seconds_limit` int(3) NOT NULL DEFAULT '120'
		COMMENT 'default 120 : 2 minutes',
	ADD `premium_until` int(32) NOT NULL DEFAULT 0,
		comment 'Unix timestamp, when will premium end?';
-- 11/4/2016 { payments }
CREATE TABLE IF NOT EXISTS payments (
	`id` int(6) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`user_id` int(20) NOT NULL,
	`type` enum('paypal', 'stripe') NOT NULL,
	`user_agent` varchar(50) NOT NULL,
	`ip` varchar(45) NOT NULL,
	`time` int(32) NOT NULL,
	`aditional_info` varchar(500)
	 /**
	 *   ^ JSON with the info returned by Stripe/Paypal
	 * Just in case we need it some day in case
	 * of a dispute.
	 **/
);