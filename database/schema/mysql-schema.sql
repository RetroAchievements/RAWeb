/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `Achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Achievements` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `GameID` bigint(20) unsigned DEFAULT NULL,
  `Title` varchar(64) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `MemAddr` text NOT NULL,
  `Progress` varchar(255) DEFAULT NULL,
  `ProgressMax` varchar(255) DEFAULT NULL,
  `ProgressFormat` varchar(50) DEFAULT NULL,
  `Points` smallint(5) unsigned NOT NULL DEFAULT 0,
  `Flags` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `type` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `unlocks_total` int(10) unsigned DEFAULT NULL,
  `unlocks_hardcore_total` int(10) unsigned DEFAULT NULL,
  `unlock_percentage` decimal(10,9) DEFAULT NULL,
  `unlock_hardcore_percentage` decimal(10,9) DEFAULT NULL,
  `DateCreated` timestamp NULL DEFAULT NULL,
  `DateModified` timestamp NULL DEFAULT current_timestamp(),
  `VotesPos` smallint(5) unsigned NOT NULL DEFAULT 0,
  `VotesNeg` smallint(5) unsigned NOT NULL DEFAULT 0,
  `BadgeName` varchar(8) DEFAULT '00001',
  `DisplayOrder` smallint(6) NOT NULL DEFAULT 0,
  `AssocVideo` varchar(255) DEFAULT NULL,
  `TrueRatio` int(10) unsigned NOT NULL DEFAULT 0,
  `Updated` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `achievements_gameid_index` (`GameID`),
  KEY `achievements_points_index` (`Points`),
  KEY `achievements_trueratio_index` (`TrueRatio`),
  KEY `achievements_user_id_foreign` (`user_id`),
  KEY `achievements_type_index` (`type`),
  KEY `achievements_game_id_published_index` (`GameID`,`Flags`),
  CONSTRAINT `achievements_game_id_foreign` FOREIGN KEY (`GameID`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Comment` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ArticleType` tinyint(3) unsigned NOT NULL,
  `ArticleID` int(10) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `commentable_type` varchar(255) DEFAULT NULL,
  `commentable_id` bigint(20) unsigned DEFAULT NULL,
  `Payload` text NOT NULL,
  `Submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `Edited` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `comment_articleid_index` (`ArticleID`),
  KEY `comments_commentable_index` (`commentable_type`,`commentable_id`),
  KEY `comment_user_id_foreign` (`user_id`),
  CONSTRAINT `comment_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Console`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Console` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `name_full` varchar(255) DEFAULT NULL,
  `name_short` varchar(255) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `EmailConfirmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EmailConfirmations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `User` varchar(20) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `EmailCookie` varchar(20) NOT NULL,
  `Expires` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `emailconfirmations_emailcookie_index` (`EmailCookie`),
  KEY `emailconfirmations_user_id_foreign` (`user_id`),
  CONSTRAINT `emailconfirmations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Forum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Forum` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `forumable_model` varchar(255) DEFAULT NULL,
  `forumable_id` bigint(20) unsigned DEFAULT NULL,
  `CategoryID` bigint(20) unsigned DEFAULT NULL,
  `Title` varchar(50) NOT NULL,
  `Description` varchar(250) NOT NULL,
  `LatestCommentID` bigint(20) unsigned DEFAULT NULL,
  `DisplayOrder` int(11) NOT NULL DEFAULT 0,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `forums_forumable_unique` (`forumable_model`,`forumable_id`),
  KEY `forums_forum_category_id_index` (`CategoryID`),
  CONSTRAINT `forums_forum_category_id_foreign` FOREIGN KEY (`CategoryID`) REFERENCES `ForumCategory` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ForumCategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ForumCategory` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(250) NOT NULL,
  `Description` varchar(250) NOT NULL,
  `DisplayOrder` int(10) unsigned NOT NULL DEFAULT 0,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ForumTopic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ForumTopic` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ForumID` bigint(20) unsigned DEFAULT NULL,
  `Title` varchar(255) NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `pinned_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `DateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `LatestCommentID` bigint(20) unsigned DEFAULT NULL,
  `RequiredPermissions` smallint(6) NOT NULL DEFAULT 0,
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `forum_topics_forum_id_index` (`ForumID`),
  KEY `forum_topics_created_at_index` (`DateCreated`),
  KEY `forumtopic_author_id_foreign` (`author_id`),
  CONSTRAINT `forumtopic_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ForumTopicComment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ForumTopicComment` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ForumTopicID` bigint(20) unsigned DEFAULT NULL,
  `Payload` text NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `authorized_at` timestamp NULL DEFAULT NULL,
  `DateCreated` timestamp NULL DEFAULT NULL,
  `DateModified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Authorised` tinyint(3) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `forum_topic_comments_forum_topic_id_index` (`ForumTopicID`),
  KEY `forum_topic_comments_created_at_index` (`DateCreated`),
  KEY `forum_topic_comments_author_id_created_at_index` (`author_id`,`DateCreated`) USING BTREE,
  KEY `forumtopiccomment_forumtopicid_authorised_datecreated_index` (`ForumTopicID`,`Authorised`,`DateCreated`),
  CONSTRAINT `forumtopiccomment_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Friends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Friends` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `related_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` smallint(5) unsigned DEFAULT NULL,
  `Friendship` tinyint(4) NOT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_relations_related_user_id_foreign` (`related_user_id`),
  KEY `user_relations_user_id_foreign` (`user_id`),
  CONSTRAINT `user_relations_related_user_id_foreign` FOREIGN KEY (`related_user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `user_relations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `GameAlternatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GameAlternatives` (
  `gameID` int(10) unsigned DEFAULT NULL,
  `gameIDAlt` int(10) unsigned DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `gamealternatives_gameid_index` (`gameID`),
  KEY `gamealternatives_gameidalt_index` (`gameIDAlt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `GameData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GameData` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(80) DEFAULT NULL,
  `ConsoleID` int(10) unsigned DEFAULT NULL,
  `ForumTopicID` bigint(20) unsigned DEFAULT NULL,
  `Flags` int(11) DEFAULT NULL,
  `ImageIcon` varchar(50) DEFAULT '/Images/000001.png',
  `ImageTitle` varchar(50) DEFAULT '/Images/000002.png',
  `ImageIngame` varchar(50) DEFAULT '/Images/000002.png',
  `ImageBoxArt` varchar(50) DEFAULT '/Images/000002.png',
  `Publisher` varchar(50) DEFAULT NULL,
  `Developer` varchar(50) DEFAULT NULL,
  `Genre` varchar(50) DEFAULT NULL,
  `Released` varchar(50) DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_at_granularity` varchar(255) DEFAULT NULL,
  `releases` text DEFAULT NULL,
  `IsFinal` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `RichPresencePatch` text DEFAULT NULL,
  `players_total` int(10) unsigned DEFAULT NULL,
  `players_hardcore` int(10) unsigned DEFAULT NULL,
  `achievement_set_version_hash` varchar(255) DEFAULT NULL,
  `achievements_published` int(10) unsigned DEFAULT NULL,
  `achievements_unpublished` int(10) unsigned DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `TotalTruePoints` int(10) unsigned NOT NULL DEFAULT 0,
  `GuideURL` varchar(255) DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `games_title_system_id_unique` (`Title`,`ConsoleID`),
  KEY `games_system_id_index` (`ConsoleID`),
  KEY `games_title_index` (`Title`),
  KEY `games_released_at_index` (`released_at`),
  KEY `games_players_total_index` (`players_total`),
  KEY `games_players_hardcore_index` (`players_hardcore`),
  KEY `gamedata_forumtopicid_foreign` (`ForumTopicID`),
  CONSTRAINT `gamedata_forumtopicid_foreign` FOREIGN KEY (`ForumTopicID`) REFERENCES `ForumTopic` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `games_systems_id_foreign` FOREIGN KEY (`ConsoleID`) REFERENCES `Console` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `LeaderboardDef`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LeaderboardDef` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `GameID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `Mem` text NOT NULL,
  `Format` varchar(50) DEFAULT '',
  `Title` varchar(255) DEFAULT 'Leaderboard Title',
  `Description` varchar(255) DEFAULT 'Leaderboard Description',
  `LowerIsBetter` tinyint(1) NOT NULL DEFAULT 0,
  `DisplayOrder` int(11) NOT NULL DEFAULT 0,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `leaderboards_game_id_index` (`GameID`),
  KEY `leaderboarddef_author_id_foreign` (`author_id`),
  CONSTRAINT `leaderboarddef_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `leaderboards_game_id_foreign` FOREIGN KEY (`GameID`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `News`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `News` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `Title` varchar(255) DEFAULT NULL,
  `lead` text DEFAULT NULL,
  `Payload` text NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `publish_at` timestamp NULL DEFAULT NULL,
  `unpublish_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `news_user_id_foreign` (`user_id`),
  CONSTRAINT `news_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rating` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ratable_model` varchar(255) DEFAULT NULL,
  `ratable_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `User` varchar(255) NOT NULL,
  `RatingObjectType` smallint(6) NOT NULL,
  `RatingID` smallint(6) NOT NULL,
  `RatingValue` smallint(6) NOT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ratings_user_rating_unique` (`User`,`RatingObjectType`,`RatingID`),
  KEY `ratings_ratable_index` (`ratable_model`,`ratable_id`),
  KEY `ratings_user_id_foreign` (`user_id`),
  CONSTRAINT `ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `SetClaim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SetClaim` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `game_id` bigint(20) unsigned NOT NULL,
  `ClaimType` int(10) unsigned NOT NULL COMMENT '0 - Primary (counts against claim total), 1 - Collaboration (does not count against claim total)',
  `SetType` int(10) unsigned NOT NULL COMMENT '0 - New set, 1 - Revision',
  `Status` int(10) unsigned NOT NULL COMMENT '0 - Active, 1 - Complete, 2 - Dropped',
  `Extension` int(10) unsigned NOT NULL COMMENT 'Number of times the claim has been extended',
  `Special` int(10) unsigned NOT NULL COMMENT '0 - Standard claim, 1 - Own Revision, 2 - Free Rollout claim, 3 - Future release approved. >=1 does not count against claim count',
  `Created` timestamp NOT NULL DEFAULT current_timestamp(),
  `Finished` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp for when the claim is completed, dropped or will expire',
  `Updated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `setclaim_game_id_foreign` (`game_id`),
  KEY `setclaim_user_id_foreign` (`user_id`),
  CONSTRAINT `setclaim_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `setclaim_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `SetRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SetRequest` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `GameID` bigint(20) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `Updated` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_game_list_entry_user_id_game_id_type_unique` (`user_id`,`GameID`,`type`),
  KEY `user_game_list_entry_game_id_foreign` (`GameID`),
  CONSTRAINT `user_game_list_entry_game_id_foreign` FOREIGN KEY (`GameID`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `user_game_list_entry_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `SiteAwards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteAwards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `AwardDate` datetime NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `AwardType` int(11) NOT NULL,
  `AwardData` int(11) DEFAULT NULL,
  `AwardDataExtra` int(11) NOT NULL DEFAULT 0,
  `DisplayOrder` smallint(6) NOT NULL DEFAULT 0 COMMENT 'Display order to show site awards in',
  PRIMARY KEY (`id`),
  KEY `siteawards_awardtype_index` (`AwardType`),
  KEY `siteawards_awarddata_awardtype_awarddate_index` (`AwardData`,`AwardType`,`AwardDate`),
  KEY `siteawards_user_id_index` (`user_id`),
  KEY `siteawards_user_id_awarddata_awardtype_awarddataextra_index` (`user_id`,`AwardData`,`AwardType`,`AwardDataExtra`),
  CONSTRAINT `siteawards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `StaticData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StaticData` (
  `NumAchievements` int(10) unsigned NOT NULL,
  `NumAwarded` int(10) unsigned NOT NULL,
  `NumGames` int(10) unsigned NOT NULL,
  `NumRegisteredUsers` int(10) unsigned NOT NULL,
  `num_hardcore_mastery_awards` int(10) unsigned NOT NULL DEFAULT 0,
  `num_hardcore_game_beaten_awards` int(10) unsigned NOT NULL DEFAULT 0,
  `last_game_hardcore_mastered_game_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `last_game_hardcore_mastered_user_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `last_game_hardcore_mastered_at` timestamp NULL DEFAULT NULL,
  `last_game_hardcore_beaten_game_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `last_game_hardcore_beaten_user_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `last_game_hardcore_beaten_at` timestamp NULL DEFAULT NULL,
  `TotalPointsEarned` int(10) unsigned NOT NULL,
  `LastAchievementEarnedID` int(10) unsigned NOT NULL,
  `LastAchievementEarnedByUser` varchar(50) NOT NULL,
  `LastAchievementEarnedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `LastRegisteredUser` varchar(50) NOT NULL,
  `LastRegisteredUserAt` timestamp NULL DEFAULT NULL,
  `LastUpdatedGameID` int(10) unsigned NOT NULL,
  `LastUpdatedAchievementID` int(10) unsigned NOT NULL,
  `LastCreatedGameID` int(10) unsigned NOT NULL,
  `LastCreatedAchievementID` int(10) unsigned NOT NULL,
  `NextGameToScan` int(10) unsigned NOT NULL DEFAULT 1,
  `NextUserIDToScan` int(10) unsigned NOT NULL DEFAULT 1,
  `Event_AOTW_AchievementID` int(10) unsigned NOT NULL DEFAULT 1,
  `Event_AOTW_ForumID` int(10) unsigned NOT NULL DEFAULT 1,
  `Event_AOTW_StartAt` timestamp NULL DEFAULT NULL,
  KEY `last_game_hardcore_mastered_game_id_foreign` (`last_game_hardcore_mastered_game_id`),
  KEY `last_game_hardcore_mastered_user_id_foreign` (`last_game_hardcore_mastered_user_id`),
  KEY `last_game_hardcore_beaten_game_id_foreign` (`last_game_hardcore_beaten_game_id`),
  KEY `last_game_hardcore_beaten_user_id_foreign` (`last_game_hardcore_beaten_user_id`),
  CONSTRAINT `last_game_hardcore_beaten_game_id_foreign` FOREIGN KEY (`last_game_hardcore_beaten_game_id`) REFERENCES `GameData` (`ID`),
  CONSTRAINT `last_game_hardcore_beaten_user_id_foreign` FOREIGN KEY (`last_game_hardcore_beaten_user_id`) REFERENCES `UserAccounts` (`ID`),
  CONSTRAINT `last_game_hardcore_mastered_game_id_foreign` FOREIGN KEY (`last_game_hardcore_mastered_game_id`) REFERENCES `GameData` (`ID`),
  CONSTRAINT `last_game_hardcore_mastered_user_id_foreign` FOREIGN KEY (`last_game_hardcore_mastered_user_id`) REFERENCES `UserAccounts` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Ticket` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticketable_model` varchar(255) DEFAULT NULL,
  `ticketable_id` bigint(20) unsigned DEFAULT NULL,
  `ticketable_author_id` bigint(20) unsigned DEFAULT NULL,
  `AchievementID` bigint(20) unsigned DEFAULT NULL,
  `reporter_id` bigint(20) unsigned DEFAULT NULL,
  `game_hash_set_id` bigint(20) unsigned DEFAULT NULL,
  `player_session_id` bigint(20) unsigned DEFAULT NULL,
  `ReportType` tinyint(3) unsigned NOT NULL,
  `Hardcore` tinyint(1) DEFAULT NULL,
  `ReportNotes` text NOT NULL,
  `ReportedAt` timestamp NULL DEFAULT NULL,
  `ResolvedAt` timestamp NULL DEFAULT NULL,
  `resolver_id` bigint(20) unsigned DEFAULT NULL,
  `ReportState` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1=submitted,2=resolved,3=declined',
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `tickets_ticketable_reporter_id_index` (`ticketable_model`,`ticketable_id`,`reporter_id`),
  KEY `tickets_created_at_index` (`ReportedAt`),
  KEY `tickets_ticketable_index` (`ticketable_model`,`ticketable_id`),
  KEY `tickets_game_hash_set_id_foreign` (`game_hash_set_id`),
  KEY `tickets_player_session_id_foreign` (`player_session_id`),
  KEY `tickets_achievement_id_reporter_id_index` (`AchievementID`,`reporter_id`),
  KEY `ticket_reporter_id_foreign` (`reporter_id`),
  KEY `ticket_resolver_id_foreign` (`resolver_id`),
  KEY `ticket_ticketable_author_id_foreign` (`ticketable_author_id`),
  CONSTRAINT `ticket_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `ticket_resolver_id_foreign` FOREIGN KEY (`resolver_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `ticket_ticketable_author_id_foreign` FOREIGN KEY (`ticketable_author_id`) REFERENCES `UserAccounts` (`ID`),
  CONSTRAINT `tickets_game_hash_set_id_foreign` FOREIGN KEY (`game_hash_set_id`) REFERENCES `game_hash_sets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_player_session_id_foreign` FOREIGN KEY (`player_session_id`) REFERENCES `player_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserAccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserAccounts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `User` varchar(32) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `SaltedPass` varchar(32) NOT NULL,
  `EmailAddress` varchar(64) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `Permissions` tinyint(4) NOT NULL COMMENT '-2=spam, -1=banned, 0=unconfirmed, 1=confirmed, 2=jr-developer, 3=developer, 4=moderator',
  `achievements_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `completion_percentage_average` decimal(10,9) DEFAULT NULL,
  `completion_percentage_average_hardcore` decimal(10,9) DEFAULT NULL,
  `RAPoints` int(11) NOT NULL,
  `RASoftcorePoints` int(11) DEFAULT 0,
  `fbUser` bigint(20) NOT NULL,
  `fbPrefs` smallint(6) DEFAULT NULL,
  `cookie` varchar(100) DEFAULT NULL,
  `appToken` varchar(60) DEFAULT NULL,
  `appTokenExpiry` datetime DEFAULT NULL,
  `websitePrefs` int(10) unsigned DEFAULT 0,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `country` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `locale` varchar(255) DEFAULT NULL,
  `locale_date` varchar(255) DEFAULT NULL,
  `locale_number` varchar(255) DEFAULT NULL,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `LastActivityID` int(10) unsigned NOT NULL DEFAULT 0,
  `Motto` varchar(50) NOT NULL DEFAULT '',
  `ContribCount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The Number of awarded achievements that this user was the author of',
  `ContribYield` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The total points allocated for achievements that this user has been the author of',
  `APIKey` varchar(60) DEFAULT NULL,
  `APIUses` int(10) unsigned NOT NULL DEFAULT 0,
  `LastGameID` int(10) unsigned NOT NULL DEFAULT 0,
  `RichPresenceMsg` varchar(255) DEFAULT NULL,
  `RichPresenceMsgDate` datetime DEFAULT NULL,
  `ManuallyVerified` tinyint(3) unsigned DEFAULT 0 COMMENT 'If 0, cannot post directly to forums without manual permission',
  `forum_verified_at` timestamp NULL DEFAULT NULL,
  `unranked_at` timestamp NULL DEFAULT NULL,
  `banned_at` timestamp NULL DEFAULT NULL,
  `muted_until` timestamp NULL DEFAULT NULL,
  `UnreadMessageCount` int(10) unsigned DEFAULT NULL,
  `TrueRAPoints` int(10) unsigned DEFAULT NULL,
  `UserWallActive` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow Posting to user wall',
  `PasswordResetToken` varchar(32) DEFAULT NULL,
  `Untracked` tinyint(1) NOT NULL DEFAULT 0,
  `email_backup` varchar(255) DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp(),
  `DeleteRequested` timestamp NULL DEFAULT NULL,
  `Deleted` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `users_username_unique` (`User`),
  KEY `users_username_untracked_index` (`User`,`Untracked`),
  KEY `users_points_weighted_untracked_index` (`TrueRAPoints`,`Untracked`),
  KEY `users_untracked_points_index` (`Untracked`,`RAPoints`),
  KEY `users_last_activity_id_index` (`LastActivityID`),
  KEY `users_points_untracked_index` (`RAPoints`,`Untracked`),
  KEY `users_points_softcore_untracked_index` (`RASoftcorePoints`,`Untracked`),
  KEY `users_unranked_at_index` (`unranked_at`),
  KEY `users_points_unranked_at_index` (`RAPoints`,`unranked_at`),
  KEY `users_points_softcore_unranked_at_index` (`RASoftcorePoints`,`unranked_at`),
  KEY `users_points_weighted_unranked_at_index` (`TrueRAPoints`,`unranked_at`),
  KEY `users_apikey_index` (`APIKey`) USING BTREE,
  KEY `users_apptoken_index` (`appToken`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `votable_model` varchar(255) DEFAULT NULL,
  `votable_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `User` varchar(50) NOT NULL,
  `AchievementID` int(10) unsigned NOT NULL,
  `Vote` tinyint(4) NOT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `votes_user_achievement_id_unique` (`User`,`AchievementID`),
  KEY `votes_votable_index` (`votable_model`,`votable_id`),
  KEY `votes_user_id_foreign` (`user_id`),
  CONSTRAINT `votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_authors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `task` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_authors_achievement_id_user_id_unique` (`achievement_id`,`user_id`),
  KEY `achievement_authors_user_id_foreign` (`user_id`),
  CONSTRAINT `achievement_authors_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_set_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_set_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `achievement_id` bigint(20) unsigned NOT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_set_achievement_unique` (`achievement_set_id`,`achievement_id`),
  KEY `achievement_set_achievements_achievement_id_foreign` (`achievement_id`),
  CONSTRAINT `achievement_set_achievements_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_set_achievements_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_set_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_set_authors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `task` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievement_set_authors_achievement_set_id_foreign` (`achievement_set_id`),
  KEY `achievement_set_authors_user_id_foreign` (`user_id`),
  CONSTRAINT `achievement_set_authors_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `achievement_set_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_set_game_hashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_set_game_hashes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `game_hash_id` bigint(20) unsigned NOT NULL,
  `compatible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `set_hash_unique` (`achievement_set_id`,`game_hash_id`),
  KEY `achievement_set_game_hashes_game_hash_id_foreign` (`game_hash_id`),
  CONSTRAINT `achievement_set_game_hashes_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `achievement_set_game_hashes_game_hash_id_foreign` FOREIGN KEY (`game_hash_id`) REFERENCES `game_hashes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_set_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_set_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned DEFAULT NULL,
  `definition` mediumtext DEFAULT NULL,
  `players_total` int(10) unsigned DEFAULT NULL,
  `players_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_published` int(10) unsigned DEFAULT NULL,
  `achievements_unpublished` int(10) unsigned DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_set_versions_achievement_set_id_version_unique` (`achievement_set_id`,`version`),
  KEY `achievement_set_versions_players_total_index` (`players_total`),
  KEY `achievement_set_versions_players_hardcore_index` (`players_hardcore`),
  CONSTRAINT `achievement_set_versions_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `players_total` int(10) unsigned DEFAULT NULL,
  `players_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_published` int(10) unsigned DEFAULT NULL,
  `achievements_unpublished` int(10) unsigned DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievement_sets_user_id_index` (`user_id`),
  KEY `achievement_sets_players_total_index` (`players_total`),
  KEY `achievement_sets_players_hardcore_index` (`players_hardcore`),
  CONSTRAINT `achievement_sets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_log_subject_index` (`subject_type`,`subject_id`),
  KEY `audit_log_causer_index` (`causer_type`,`causer_id`),
  KEY `audit_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_model_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_model_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `auth_model_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_model_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_model_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `auth_model_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_role_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `auth_role_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `auth_role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display` int(10) unsigned NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emulator_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulator_releases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emulator_id` int(10) unsigned NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `stable` tinyint(1) NOT NULL DEFAULT 0,
  `minimum` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emulator_releases_emulator_id_version_unique` (`emulator_id`,`version`),
  CONSTRAINT `emulator_releases_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emulators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `handle` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `game_hash_column` text DEFAULT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emulators_integration_id_unique` (`integration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_achievement_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_achievement_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) unsigned NOT NULL,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_achievement_sets_game_id_foreign` (`game_id`),
  KEY `game_achievement_sets_achievement_set_id_foreign` (`achievement_set_id`),
  CONSTRAINT `game_achievement_sets_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_achievement_sets_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_hash_set_hashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_hash_set_hashes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_hash_set_id` bigint(20) unsigned NOT NULL,
  `game_hash_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_hash_set_hashes_game_hash_set_id_game_hash_id_unique` (`game_hash_set_id`,`game_hash_id`),
  KEY `game_hash_set_hashes_game_hash_id_foreign` (`game_hash_id`),
  CONSTRAINT `game_hash_set_hashes_game_hash_id_foreign` FOREIGN KEY (`game_hash_id`) REFERENCES `game_hashes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_hash_set_hashes_game_hash_set_id_foreign` FOREIGN KEY (`game_hash_set_id`) REFERENCES `game_hash_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_hash_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_hash_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) unsigned NOT NULL,
  `compatible` tinyint(1) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_hash_sets_game_id_foreign` (`game_id`),
  CONSTRAINT `game_hash_sets_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_hashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_hashes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `system_id` int(10) unsigned DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `crc` varchar(8) DEFAULT NULL,
  `md5` varchar(32) DEFAULT NULL,
  `sha1` varchar(40) DEFAULT NULL,
  `file_crc` varchar(8) DEFAULT NULL,
  `file_md5` varchar(32) DEFAULT NULL,
  `file_sha1` varchar(40) DEFAULT NULL,
  `file_name_md5` varchar(32) DEFAULT NULL,
  `compatibility` varchar(255) DEFAULT NULL,
  `game_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `labels` varchar(255) DEFAULT NULL,
  `file_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_names`)),
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `regions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`regions`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `source` varchar(255) DEFAULT NULL,
  `source_status` varchar(255) DEFAULT NULL,
  `source_version` varchar(255) DEFAULT NULL,
  `patch_url` varchar(255) DEFAULT NULL,
  `imported_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_hashes_system_id_hash_unique` (`system_id`,`hash`),
  UNIQUE KEY `game_hashes_md5_unique` (`md5`),
  KEY `game_hashes_user_id_foreign` (`user_id`),
  KEY `game_hashes_game_id_foreign` (`game_id`),
  CONSTRAINT `game_hashes_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_hashes_system_id_foreign` FOREIGN KEY (`system_id`) REFERENCES `Console` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_hashes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_set_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_set_games` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_set_id` bigint(20) unsigned NOT NULL,
  `game_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_set_games_game_set_id_foreign` (`game_set_id`),
  KEY `game_set_games_game_id_foreign` (`game_id`),
  CONSTRAINT `game_set_games_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_set_games_game_set_id_foreign` FOREIGN KEY (`game_set_id`) REFERENCES `game_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `definition` text DEFAULT NULL,
  `legacy_game_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_sets_user_id_index` (`user_id`),
  CONSTRAINT `game_sets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integration_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_releases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `stable` tinyint(1) NOT NULL DEFAULT 0,
  `minimum` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integration_releases_version_unique` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leaderboard_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leaderboard_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leaderboard_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `score` bigint(20) NOT NULL,
  `trigger_id` bigint(20) unsigned DEFAULT NULL,
  `player_session_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leaderboard_entries_leaderboard_id_user_id_unique` (`leaderboard_id`,`user_id`),
  KEY `leaderboard_entries_user_id_foreign` (`user_id`),
  KEY `leaderboard_entries_trigger_id_foreign` (`trigger_id`),
  KEY `leaderboard_entries_player_session_id_foreign` (`player_session_id`),
  CONSTRAINT `leaderboard_entries_player_session_id_foreign` FOREIGN KEY (`player_session_id`) REFERENCES `player_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaderboard_entries_trigger_id_foreign` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaderboard_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `uuid` char(36) DEFAULT NULL,
  `collection_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL,
  `conversions_disk` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memory_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `memory_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_hash_set_id` bigint(20) unsigned DEFAULT NULL,
  `game_id` bigint(20) unsigned DEFAULT NULL,
  `address` int(10) unsigned NOT NULL COMMENT 'Decimal -> Hex',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `body` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memory_notes_game_id_index` (`game_id`),
  KEY `memory_notes_address_index` (`address`),
  KEY `memory_notes_game_hash_set_id_address_index` (`game_hash_set_id`,`address`),
  KEY `memory_notes_user_id_index` (`user_id`),
  CONSTRAINT `codenotes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `memory_notes_game_hash_set_id_foreign` FOREIGN KEY (`game_hash_set_id`) REFERENCES `game_hash_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `memory_notes_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_thread_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_thread_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `num_unread` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_thread_participants_user_id_foreign` (`user_id`),
  KEY `message_thread_participants_thread_id_foreign` (`thread_id`),
  CONSTRAINT `message_thread_participants_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_thread_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `num_messages` int(11) NOT NULL DEFAULT 0,
  `last_message_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned NOT NULL,
  `Title` text NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `Unread` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `messages_unread_index` (`Unread`),
  KEY `messages_thread_id_foreign` (`thread_id`),
  KEY `messages_author_id_foreign` (`author_id`),
  CONSTRAINT `messages_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `messages_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_achievement_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_achievement_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `achievements_total` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_beat` int(10) unsigned DEFAULT NULL,
  `achievements_beat_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_beat_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `beaten_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `beaten_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `last_played_at` timestamp NULL DEFAULT NULL,
  `playtime_total` bigint(20) unsigned DEFAULT NULL,
  `time_taken` bigint(20) unsigned DEFAULT NULL,
  `time_taken_hardcore` bigint(20) unsigned DEFAULT NULL,
  `beaten_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`beaten_dates`)),
  `beaten_dates_hardcore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`beaten_dates_hardcore`)),
  `completion_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates`)),
  `completion_dates_hardcore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates_hardcore`)),
  `beaten_at` timestamp NULL DEFAULT NULL,
  `beaten_hardcore_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_hardcore_at` timestamp NULL DEFAULT NULL,
  `last_unlock_at` timestamp NULL DEFAULT NULL,
  `last_unlock_hardcore_at` timestamp NULL DEFAULT NULL,
  `first_unlock_at` timestamp NULL DEFAULT NULL,
  `first_unlock_hardcore_at` timestamp NULL DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `points_hardcore` int(10) unsigned DEFAULT NULL,
  `points_weighted_total` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_achievement_sets_user_id_achievement_set_id_unique` (`user_id`,`achievement_set_id`),
  KEY `player_achievement_sets_achievement_set_id_foreign` (`achievement_set_id`),
  CONSTRAINT `player_achievement_sets_achievement_set_id_foreign` FOREIGN KEY (`achievement_set_id`) REFERENCES `achievement_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_achievement_sets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `achievement_id` bigint(20) unsigned NOT NULL,
  `trigger_id` bigint(20) unsigned DEFAULT NULL,
  `player_session_id` bigint(20) unsigned DEFAULT NULL,
  `unlocker_id` bigint(20) unsigned DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `unlocked_hardcore_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_achievements_user_id_achievement_id_unique` (`user_id`,`achievement_id`),
  KEY `player_achievements_unlocked_at_index` (`unlocked_at`),
  KEY `player_achievements_trigger_id_foreign` (`trigger_id`),
  KEY `player_achievements_player_session_id_foreign` (`player_session_id`),
  KEY `player_achievements_unlocker_id_foreign` (`unlocker_id`),
  KEY `player_achievements_unlocked_hardcore_at_index` (`unlocked_hardcore_at`),
  KEY `player_achievements_achievement_id_user_id_unlocked_hardcore_at` (`achievement_id`,`user_id`,`unlocked_hardcore_at`),
  KEY `player_achievements_user_date_achievement` (`user_id`,`unlocked_at`,`unlocked_hardcore_at`,`achievement_id`),
  CONSTRAINT `player_achievements_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `player_achievements_player_session_id_foreign` FOREIGN KEY (`player_session_id`) REFERENCES `player_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_achievements_trigger_id_foreign` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_achievements_unlocker_id_foreign` FOREIGN KEY (`unlocker_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `player_achievements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_games` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `game_id` bigint(20) unsigned NOT NULL,
  `game_hash_id` bigint(20) unsigned DEFAULT NULL,
  `achievement_set_version_hash` varchar(255) DEFAULT NULL,
  `achievements_total` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_softcore` int(10) unsigned DEFAULT NULL,
  `achievements_beat` int(10) unsigned DEFAULT NULL,
  `achievements_beat_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_beat_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `beaten_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `beaten_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `last_played_at` timestamp NULL DEFAULT NULL,
  `playtime_total` bigint(20) unsigned DEFAULT NULL,
  `time_taken` bigint(20) unsigned DEFAULT NULL,
  `time_taken_hardcore` bigint(20) unsigned DEFAULT NULL,
  `beaten_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`beaten_dates`)),
  `beaten_dates_hardcore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`beaten_dates_hardcore`)),
  `completion_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates`)),
  `completion_dates_hardcore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates_hardcore`)),
  `beaten_at` timestamp NULL DEFAULT NULL,
  `beaten_hardcore_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_hardcore_at` timestamp NULL DEFAULT NULL,
  `last_unlock_at` timestamp NULL DEFAULT NULL,
  `last_unlock_hardcore_at` timestamp NULL DEFAULT NULL,
  `first_unlock_at` timestamp NULL DEFAULT NULL,
  `first_unlock_hardcore_at` timestamp NULL DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `points_hardcore` int(10) unsigned DEFAULT NULL,
  `points_weighted_total` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_games_user_id_game_id_unique` (`user_id`,`game_id`),
  KEY `player_games_created_at_index` (`created_at`),
  KEY `player_games_game_hash_id_foreign` (`game_hash_id`),
  KEY `player_games_game_id_achievement_set_version_hash_index` (`game_id`,`achievement_set_version_hash`),
  KEY `player_games_game_id_user_id_index` (`game_id`,`user_id`),
  KEY `player_games_game_id_achievements_unlocked_index` (`game_id`,`achievements_unlocked`),
  KEY `player_games_game_id_achievements_unlocked_hardcore_index` (`game_id`,`achievements_unlocked_hardcore`),
  KEY `player_games_game_id_achievements_unlocked_softcore_index` (`game_id`,`achievements_unlocked_softcore`),
  CONSTRAINT `player_games_game_hash_id_foreign` FOREIGN KEY (`game_hash_id`) REFERENCES `game_hashes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_games_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `player_games_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `game_hash_set_id` bigint(20) unsigned DEFAULT NULL,
  `game_hash_id` bigint(20) unsigned DEFAULT NULL,
  `game_id` bigint(20) unsigned DEFAULT NULL,
  `hardcore` tinyint(1) DEFAULT NULL,
  `rich_presence` text DEFAULT NULL,
  `rich_presence_updated_at` timestamp NULL DEFAULT NULL,
  `duration` int(10) unsigned NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_sessions_game_hash_set_id_foreign` (`game_hash_set_id`),
  KEY `player_sessions_game_hash_id_foreign` (`game_hash_id`),
  KEY `player_sessions_game_id_user_id_rich_presence_updated_at_index` (`game_id`,`user_id`,`rich_presence_updated_at`),
  KEY `player_sessions_user_id_game_id_rich_presence_updated_at_index` (`user_id`,`game_id`,`rich_presence_updated_at`),
  CONSTRAINT `player_sessions_game_hash_id_foreign` FOREIGN KEY (`game_hash_id`) REFERENCES `game_hashes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_sessions_game_hash_set_id_foreign` FOREIGN KEY (`game_hash_set_id`) REFERENCES `game_hash_sets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_sessions_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `player_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `system_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `last_game_id` bigint(20) unsigned DEFAULT NULL,
  `stat_updated_at` timestamp NULL DEFAULT NULL,
  `value` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_stats_user_id_system_id_type_unique` (`user_id`,`system_id`,`type`),
  KEY `player_stats_user_id_index` (`user_id`),
  KEY `player_stats_system_id_index` (`system_id`),
  KEY `player_stats_type_index` (`type`),
  KEY `player_stats_last_game_id_foreign` (`last_game_id`),
  CONSTRAINT `player_stats_last_game_id_foreign` FOREIGN KEY (`last_game_id`) REFERENCES `GameData` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `player_stats_system_id_foreign` FOREIGN KEY (`system_id`) REFERENCES `Console` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `player_stats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue_failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue_failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue_job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `queue_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(255) NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `state` tinyint(1) NOT NULL COMMENT 'Whether UserID is subscribed (1) or unsubscribed (0)',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_subject_type_subject_id_user_id_unique` (`subject_type`,`subject_id`,`user_id`),
  KEY `subscription_user_id_foreign` (`user_id`),
  CONSTRAINT `subscription_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_status` (
  `kind` varchar(255) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `remaining` int(10) unsigned DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_emulators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_emulators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `system_id` int(10) unsigned NOT NULL,
  `emulator_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_emulators_system_id_foreign` (`system_id`),
  KEY `system_emulators_emulator_id_foreign` (`emulator_id`),
  CONSTRAINT `system_emulators_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `system_emulators_system_id_foreign` FOREIGN KEY (`system_id`) REFERENCES `Console` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `tag_id` bigint(20) unsigned NOT NULL,
  `taggable_type` varchar(255) NOT NULL,
  `taggable_id` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `taggables_tag_id_taggable_id_taggable_type_unique` (`tag_id`,`taggable_id`,`taggable_type`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `type` varchar(255) DEFAULT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `triggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `triggers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `triggerable_type` varchar(255) NOT NULL,
  `triggerable_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `version` int(10) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `type` text DEFAULT NULL,
  `stat` varchar(255) DEFAULT NULL,
  `stat_goal` varchar(255) DEFAULT NULL,
  `stat_format` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `triggers_triggerable_type_triggerable_id_version_unique` (`triggerable_type`,`triggerable_id`,`version`),
  KEY `triggers_triggerable_type_triggerable_id_index` (`triggerable_type`,`triggerable_id`),
  KEY `triggers_user_id_foreign` (`user_id`),
  CONSTRAINT `triggers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `subject_context` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activities_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `user_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `user_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_connections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(60) NOT NULL,
  `provider_user_id` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_secret` varchar(255) DEFAULT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `expires` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `raw` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_connections_user_id_provider_provider_user_id_unique` (`user_id`,`provider`,`provider_user_id`),
  KEY `user_connections_provider_provider_user_id_index` (`provider`,`provider_user_id`),
  CONSTRAINT `user_connections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_usernames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_usernames` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_usernames_user_id_username_unique` (`user_id`,`username`),
  KEY `user_usernames_username_index` (`username`),
  CONSTRAINT `user_usernames_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2000_01_01_000000_drop_underscore_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2012_10_03_133633_create_base_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2014_10_12_000001_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2014_10_12_000002_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2014_10_12_000003_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2014_10_12_000004_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2016_06_01_000001_create_oauth_auth_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2016_06_01_000002_create_oauth_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2016_06_01_000003_create_oauth_refresh_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2016_06_01_000004_create_oauth_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2016_06_01_000005_create_oauth_personal_access_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2016_07_01_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2017_01_01_000001_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2017_01_01_000001_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2018_01_01_000001_create_sync_status_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2018_09_01_000001_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2018_09_01_000001_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2018_09_01_000001_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2018_09_01_000001_create_tag_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2018_09_01_000001_create_websockets_statistics_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2018_10_01_000001_create_system_emulator_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2018_10_01_000001_create_triggers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2018_10_01_000001_create_user_meta_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2018_10_02_000002_create_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2018_10_02_000003_create_achievements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2018_10_02_000004_create_game_meta_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2018_10_03_000001_create_player_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2018_10_03_000002_create_player_achievements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2018_10_03_000003_create_leaderboard_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2018_10_04_000001_create_player_achievement_sets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2018_10_05_000001_create_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2018_10_05_000001_create_forum_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2018_10_05_000001_create_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2018_10_05_000001_create_news_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2018_10_05_000001_create_ratings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2018_10_05_000001_create_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2018_10_05_000001_create_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2022_10_01_000000_update_ticket_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2023_03_03_000000_update_game_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2023_04_08_000001_update_set_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2023_07_31_000000_update_achievements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2023_08_13_151811_drop_deleted_models_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2023_08_19_000000_update_staticdata_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2023_09_01_000000_update_player_achievements_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2023_09_01_154331_update_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2023_09_15_000000_update_player_games_player_achievement_sets',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2023_09_29_000000_update_site_awards_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2023_10_08_000000_update_player_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2023_10_08_000001_create_job_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2023_10_09_000000_update_player_tables_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2023_10_09_000001_update_achievements_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2023_10_28_000000_update_player_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2023_10_29_000000_update_player_games_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2023_10_30_000000_update_player_achievements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2023_10_30_000000_update_player_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2023_11_06_000000_create_player_stats_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2023_11_09_000000_update_site_awards_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2014_10_12_000001_create_password_reset_tokens_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2023_11_11_131859_add_event_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2023_11_11_131900_add_batch_uuid_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2023_11_19_000000_update_user_accounts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2023_11_20_000000_update_player_stats_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2023_11_25_000000_create_message_threads_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2023_11_25_000001_create_message_thread_participants',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2023_11_25_000002_update_messages_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2023_12_03_112540_remove_teams_fields',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2023_12_03_160441_drop_activity_and_awarded_tables',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2023_12_08_000000_update_useraccounts_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2023_12_16_000000_update_gamehashlibrary_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2024_01_24_000000_update_codenotes_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2024_02_02_000001_update_forum_topic_comment_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2024_03_09_000000_update_gamehashlibrary_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2024_03_16_000000_update_codenotes_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2024_03_16_000001_update_comment_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2024_03_16_000001_update_ticket_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2024_03_17_000000_update_subscription_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2024_03_22_000000_update_forum_topic_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2024_03_17_000000_update_siteawards_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2024_03_22_00000_update_set_requests_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2024_03_23_000000_update_setclaim_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2024_03_29_000000_update_forum_topic_comment_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2024_03_30_000000_update_leaderboarddef_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2024_03_30_000001_update_messages_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2024_04_03_000000_update_player_sessions',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2024_03_26_000000_update_news_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2024_04_13_000000_update_gamedata_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2024_04_06_000000_update_forum_topic_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2024_04_27_000000_update_game_sets_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2024_05_10_000000_drop_leaderboardentry_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2024_05_12_000000_create_achievement_set_game_hashes_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2024_05_19_000000_update_achievements_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2024_05_19_000000_update_friends_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2024_05_19_000000_update_game_hashes_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2024_05_20_000000_update_ticket_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2024_05_21_000000_update_forumtopiccomment_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2024_05_21_000000_update_leaderboarddef_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2024_05_21_000000_update_setclaim_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2024_05_21_000000_update_siteawards_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2024_06_08_000000_drop_websockets_statistics_entries_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2024_06_18_000000_update_gamedata_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2024_07_03_000000_update_codenotes_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2024_07_27_000000_update_forumtopiccomment_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2024_05_25_000001_update_player_games_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2024_08_07_000000_update_emailconfirmations_table',19);