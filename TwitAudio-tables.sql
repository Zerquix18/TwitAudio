/**
 * TwitAudio tables
 * Rewritten in May 27, 2016
 * Because they sucked
 * I'm glad I did this when the site wasn't visited :)
 */

CREATE TABLE IF NOT EXISTS `users` (
	`id`                  int(20) unsigned NOT NULL,

	`username`            varchar(15)      NOT NULL,
	`name`                varchar(20)      NOT NULL,
	`bio`                 varchar(160)     NOT NULL,
	`avatar`              varchar(100)     NOT NULL,
	`access_token`        varchar(50)      NOT NULL,
	`access_token_secret` varchar(50)      NOT NULL,

	`status`              enum('1', '0')   NOT NULL
										   COMMENT '1 ok 0 banned' DEFAULT '1',
	`is_verified`         enum('1', '0')   NOT NULL,
	`favs_privacy`        enum('public', 'private') NOT NULL,
	`audios_privacy`      enum('public', 'private') NOT NULL,

	/* a unix timestamp that will fail on 2038 */
	`date_added`          int(32)          NOT NULL COMMENT 'Unix timestamp',
	`upload_limit`        int(3)           NOT NULL DEFAULT 120
										   COMMENT 'In seconds',
	`premium_until`       int(32)          NOT NULL DEFAULT 0
										   COMMENT 'unix timestamp',
	`register_ip`         int unsigned     NOT NULL DEFAULT 0,

	PRIMARY KEY (`id`),
	UNIQUE      (`username`),
	FULLTEXT    (`username`, `name`, `bio`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `audios` (
	`id`          char(6)          NOT NULL,
	`user_id`     int(20) unsigned NOT NULL,

	`audio_url`   char(36), -- NULL when is a reply :)
	`reply_to`    char(6),  -- null means not reply and filled means replying
	`description` varchar(160)     NOT NULL,

	`twitter_id`  int(25) unsigned NOT NULL DEFAULT 0,
	`date_added`  int(32) unsigned NOT NULL COMMENT 'Unix Timestamp',
	`plays`       int(8)  unsigned NOT NULL DEFAULT 0,
	`favorites`   int(8)  unsigned NOT NULL DEFAULT 0,
	`duration`    int(3)  unsigned NOT NULL,
	`status`      enum('1', '0')   NOT NULL DEFAULT '1' COMMENT '1ok 0deleted',
	`is_voice`    enum('1', '0')   NOT NULL,

	PRIMARY KEY (`id`),
	FOREIGN KEY (`user_id`)  REFERENCES users(`id`)  ON DELETE CASCADE,
	FULLTEXT    (`description`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `plays` (
	`user_ip`    int     unsigned NOT NULL,
	`audio_id`   char(6)          NOT NULL,
	`date_added` int(32) unsigned NOT NULL,

	PRIMARY KEY (`user_ip`, `audio_id`),
	FOREIGN KEY (`audio_id`) REFERENCES audios(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `favorites` (
	`user_id`    int(20) unsigned NOT NULL,
	`audio_id`   char(6)          NOT NULL,
	`date_added` int(32) unsigned NOT NULL,

	PRIMARY KEY (`user_id`, `audio_id`),
	FOREIGN KEY (`user_id`)  REFERENCES users(`id`)  ON DELETE CASCADE,
	FOREIGN KEY (`audio_id`) REFERENCES audios(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sessions` (
	`id`         char(32)         NOT NULL,
	`user_id`    int(20) unsigned NOT NULL,
	`user_ip`    int     unsigned NOT NULL,
	`date_added` int(32) unsigned NOT NULL,
	`is_mobile`  enum('1', '0')   NOT NULL,

	PRIMARY KEY (`id`),
	FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `following_cache` (
	`user_id`    int(20) unsigned NOT NULL,
	/** is ^ following ↓ ? */
	`following`  int(20) unsigned NOT NULL,
	`date_added` int(32) unsigned NOT NULL,
	`result`     enum('1', '0')   NOT NULL,

	PRIMARY KEY (`user_id`, `following`),
	FOREIGN KEY (`user_id`)   REFERENCES users(`id`) ON DELETE CASCADE,
	FOREIGN KEY (`following`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payments` (
	`id`         int(6)  unsigned         NOT NULL AUTO_INCREMENT,
	`user_id`    int(20) unsigned         NOT NULL,
	`method`     enum('paypal', 'stripe') NOT NULL,
	/** fucking user agents this is the max I'll take */
	`user_agent` varchar(2000)            NOT NULL,
	`user_ip`    int     unsigned         NOT NULL,
	`date_added` int(32) unsigned         NOT NULL,

	PRIMARY KEY (`id`),
	FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;