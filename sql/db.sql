/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuration` (
                                      `key` varchar(255) NOT NULL,
                                      `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `uniquekey` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `guid` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `creator` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `content` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                              `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                              `link` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `unread` int(11) NOT NULL,
                              `feed` int(11) NOT NULL,
                              `favorite` tinyint(1) NOT NULL,
                              `pubdate` int(11) NOT NULL,
                              `syncId` int(11) NOT NULL,
                              PRIMARY KEY (`id`),
                              KEY `indexfeed` (`feed`),
                              KEY `indexunread` (`unread`),
                              KEY `indexfavorite` (`favorite`),
                              KEY `dba_idx_event_3` (`feed`,`unread`),
                              KEY `dba_idx_event_4` (`pubdate`),
                              KEY `dba_idx_event_5` (`guid`,`feed`),
                              KEY `indexguidfeed` (`guid`,`feed`),
                              KEY `dba_idx_event_6` (`creator`),
                              KEY `dba_idx_event_7` (`feed`,`unread`,`pubdate`),
                              KEY `dba_idx_event_8` (`title`),
                              KEY `dba_idx_event_9` (`guid`),
                              FULLTEXT KEY `title` (`title`),
                              FULLTEXT KEY `title_2` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=1295775 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `influx` CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`dba`@`localhost`*/ /*!50003 trigger tr_b_ins_event before insert on event for each row
begin
    if (new.pubdate is null)
    then
        set new.pubdate = unix_timestamp();
    end if;
end */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `influx` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;

--
-- Table structure for table `flux`
--

DROP TABLE IF EXISTS `flux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flux` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `name` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
                             `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                             `website` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                             `url` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
                             `lastupdate` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
                             `folder` int(11) NOT NULL,
                             `isverbose` tinyint(1) NOT NULL,
                             `lastSyncInError` int(1) NOT NULL DEFAULT 0,
                             PRIMARY KEY (`id`),
                             KEY `indexfolder` (`folder`),
                             KEY `dba_idx_flux_1` (`id`,`name`),
                             KEY `dba_idx_flux_2` (`name`,`id`),
                             KEY `dba_idx_flux_3` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `folder`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `name` varchar(225) NOT NULL,
                               `parent` int(11) NOT NULL,
                               `isopen` tinyint(1) NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `login` varchar(225) NOT NULL,
                             `password` varchar(225) NOT NULL,
                             `email` varchar(225) NOT NULL,
                             `otpSecret` varchar(225) DEFAULT NULL,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
