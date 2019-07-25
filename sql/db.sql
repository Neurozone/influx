use influx;

--
-- Table structure for table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuration`
(
    `key`   varchar(255)                            NOT NULL,
    `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniquekey` (`key`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items`
(
    `id`          int(11)                                  NOT NULL AUTO_INCREMENT,
    `guid`        varchar(45) COLLATE utf8mb4_unicode_ci   NOT NULL,
    `title`       varchar(255) COLLATE utf8mb4_unicode_ci  NOT NULL,
    `creator`     varchar(225) COLLATE utf8mb4_unicode_ci  NOT NULL,
    `content`     mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `description` mediumtext COLLATE utf8mb4_unicode_ci    NOT NULL,
    `link`        varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
    `unread`      int(11)                                  NOT NULL,
    `feed`        int(11)                                  NOT NULL,
    `favorite`    tinyint(1)                               NOT NULL,
    `pubdate`     int(11)                                  NOT NULL,
    `syncId`      int(11)                                  NOT NULL,
    PRIMARY KEY (`id`),
    KEY `indexfeed` (`feed`),
    KEY `indexunread` (`unread`),
    KEY `indexfavorite` (`favorite`),
    KEY `dba_idx_event_3` (`feed`, `unread`),
    KEY `dba_idx_event_4` (`pubdate`),
    KEY `dba_idx_event_5` (`guid`, `feed`),
    KEY `indexguidfeed` (`guid`, `feed`),
    KEY `dba_idx_event_6` (`creator`),
    KEY `dba_idx_event_7` (`feed`, `unread`, `pubdate`),
    KEY `dba_idx_event_8` (`title`),
    KEY `dba_idx_event_9` (`guid`),
    FULLTEXT KEY `title` (`title`),
    FULLTEXT KEY `title_2` (`title`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Table structure for table `flux`
--

DROP TABLE IF EXISTS `flux`;
CREATE TABLE `flux`
(
    `id`              int(11)                                 NOT NULL AUTO_INCREMENT,
    `name`            varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description`     mediumtext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `website`         mediumtext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `url`             mediumtext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `lastupdate`      varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
    `folder`          int(11)                                 NOT NULL,
    `isverbose`       tinyint(1)                              NOT NULL,
    `lastSyncInError` int(1)                                  NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `indexfolder` (`folder`),
    KEY `dba_idx_flux_1` (`id`, `name`),
    KEY `dba_idx_flux_2` (`name`, `id`),
    KEY `dba_idx_flux_3` (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

--
-- Table structure for table `folder`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category`
(
    `id`     int(11)      NOT NULL AUTO_INCREMENT,
    `name`   varchar(225) NOT NULL,
    `parent` int(11)      NOT NULL,
    `isopen` tinyint(1)   NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`
(
    `id`        int(11)      NOT NULL AUTO_INCREMENT,
    `login`     varchar(225) NOT NULL,
    `password`  varchar(225) NOT NULL,
    `email`     varchar(225) NOT NULL,
    `otpSecret` varchar(225) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
