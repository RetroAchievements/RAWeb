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
  `trigger_id` bigint(20) unsigned DEFAULT NULL,
  `unlocks_total` int(10) unsigned DEFAULT NULL,
  `unlocks_hardcore_total` int(10) unsigned DEFAULT NULL,
  `unlock_percentage` decimal(10,9) DEFAULT NULL,
  `unlock_hardcore_percentage` decimal(10,9) DEFAULT NULL,
  `visible_user_comments_total` int(10) unsigned NOT NULL DEFAULT 0,
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
  KEY `achievements_gameid_datemodified_deleted_at_index` (`GameID`,`DateModified`,`deleted_at`),
  KEY `achievements_trigger_id_index` (`trigger_id`),
  CONSTRAINT `achievements_game_id_foreign` FOREIGN KEY (`GameID`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievements_trigger_id_foreign` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
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
  KEY `comment_user_id_submitted_index` (`user_id`,`Submitted`),
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
DROP TABLE IF EXISTS `GameData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GameData` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(80) DEFAULT NULL,
  `sort_title` varchar(255) DEFAULT NULL,
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
  `released_at` timestamp NULL DEFAULT NULL,
  `released_at_granularity` varchar(255) DEFAULT NULL,
  `trigger_id` bigint(20) unsigned DEFAULT NULL,
  `RichPresencePatch` text DEFAULT NULL,
  `players_total` int(10) unsigned DEFAULT NULL,
  `players_hardcore` int(10) unsigned DEFAULT NULL,
  `times_beaten` int(11) NOT NULL DEFAULT 0,
  `times_beaten_hardcore` int(11) NOT NULL DEFAULT 0,
  `median_time_to_beat` int(11) DEFAULT NULL,
  `median_time_to_beat_hardcore` int(11) DEFAULT NULL,
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
  KEY `gamedata_sort_title_index` (`sort_title`),
  KEY `gamedata_trigger_id_index` (`trigger_id`),
  CONSTRAINT `gamedata_forumtopicid_foreign` FOREIGN KEY (`ForumTopicID`) REFERENCES `forum_topics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gamedata_trigger_id_foreign` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
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
  `trigger_id` bigint(20) unsigned DEFAULT NULL,
  `top_entry_id` bigint(20) unsigned DEFAULT NULL,
  `Created` timestamp NULL DEFAULT current_timestamp(),
  `Updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `leaderboards_game_id_index` (`GameID`),
  KEY `leaderboarddef_author_id_foreign` (`author_id`),
  KEY `leaderboarddef_trigger_id_index` (`trigger_id`),
  KEY `leaderboarddef_top_entry_id_foreign` (`top_entry_id`),
  CONSTRAINT `leaderboarddef_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `leaderboarddef_top_entry_id_foreign` FOREIGN KEY (`top_entry_id`) REFERENCES `leaderboard_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaderboarddef_trigger_id_foreign` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leaderboards_game_id_foreign` FOREIGN KEY (`GameID`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
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
  KEY `setrequest_gameid_type_index` (`GameID`,`type`),
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
  `game_hash_id` bigint(20) unsigned DEFAULT NULL,
  `emulator_id` int(10) unsigned DEFAULT NULL,
  `emulator_version` varchar(32) DEFAULT NULL,
  `emulator_core` varchar(96) DEFAULT NULL,
  `reporter_id` bigint(20) unsigned DEFAULT NULL,
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
  KEY `tickets_achievement_id_reporter_id_index` (`AchievementID`,`reporter_id`),
  KEY `ticket_reporter_id_foreign` (`reporter_id`),
  KEY `ticket_resolver_id_foreign` (`resolver_id`),
  KEY `ticket_ticketable_author_id_foreign` (`ticketable_author_id`),
  KEY `ticket_achievementid_reportstate_deleted_at_index` (`AchievementID`,`ReportState`,`deleted_at`),
  KEY `tickets_game_hash_id_foreign` (`game_hash_id`),
  KEY `tickets_emulator_id_foreign` (`emulator_id`),
  CONSTRAINT `ticket_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `ticket_resolver_id_foreign` FOREIGN KEY (`resolver_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `ticket_ticketable_author_id_foreign` FOREIGN KEY (`ticketable_author_id`) REFERENCES `UserAccounts` (`ID`),
  CONSTRAINT `tickets_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_game_hash_id_foreign` FOREIGN KEY (`game_hash_id`) REFERENCES `game_hashes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UserAccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserAccounts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ulid` char(26) DEFAULT NULL,
  `User` varchar(32) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `visible_role_id` bigint(20) unsigned DEFAULT NULL,
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
  UNIQUE KEY `useraccounts_display_name_unique` (`display_name`),
  UNIQUE KEY `useraccounts_ulid_unique` (`ulid`),
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
  KEY `users_apptoken_index` (`appToken`) USING BTREE,
  KEY `useraccounts_lastlogin_deleted_index` (`LastLogin`,`Deleted`),
  KEY `useraccounts_visible_role_id_index` (`visible_role_id`),
  CONSTRAINT `useraccounts_visible_role_id_foreign` FOREIGN KEY (`visible_role_id`) REFERENCES `auth_roles` (`id`) ON DELETE SET NULL
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
  UNIQUE KEY `achievement_authors_achievement_id_user_id_task_unique` (`achievement_id`,`user_id`,`task`),
  KEY `achievement_authors_user_id_foreign` (`user_id`),
  CONSTRAINT `achievement_authors_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_authors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_maintainer_unlocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_maintainer_unlocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `player_achievement_id` bigint(20) unsigned NOT NULL,
  `maintainer_id` bigint(20) unsigned NOT NULL,
  `achievement_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `achievement_maintainer_unlocks_player_achievement_id_foreign` (`player_achievement_id`),
  KEY `achievement_maintainer_unlocks_maintainer_id_foreign` (`maintainer_id`),
  KEY `achievement_maintainer_unlocks_achievement_id_foreign` (`achievement_id`),
  CONSTRAINT `achievement_maintainer_unlocks_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_maintainer_unlocks_maintainer_id_foreign` FOREIGN KEY (`maintainer_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_maintainer_unlocks_player_achievement_id_foreign` FOREIGN KEY (`player_achievement_id`) REFERENCES `player_achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `achievement_maintainers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_maintainers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `effective_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievement_maintainers_achievement_id_foreign` (`achievement_id`),
  KEY `achievement_maintainers_user_id_foreign` (`user_id`),
  CONSTRAINT `achievement_maintainers_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `achievement_maintainers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `achievement_set_incompatible_game_hashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_set_incompatible_game_hashes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `game_hash_id` bigint(20) unsigned NOT NULL,
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
  `players_total` int(10) unsigned DEFAULT NULL,
  `players_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_first_published_at` datetime DEFAULT NULL,
  `achievements_published` int(10) unsigned DEFAULT NULL,
  `achievements_unpublished` int(10) unsigned DEFAULT NULL,
  `points_total` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `times_completed` int(11) NOT NULL DEFAULT 0,
  `times_completed_hardcore` int(11) NOT NULL DEFAULT 0,
  `median_time_to_complete` int(11) DEFAULT NULL,
  `median_time_to_complete_hardcore` int(11) DEFAULT NULL,
  `image_asset_path` varchar(50) NOT NULL DEFAULT '/Images/000001.png',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievement_sets_players_total_index` (`players_total`),
  KEY `achievement_sets_players_hardcore_index` (`players_hardcore`)
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
DROP TABLE IF EXISTS `downloads_popularity_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `downloads_popularity_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `ordered_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ordered_ids`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `downloads_popularity_metrics_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emulator_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulator_downloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emulator_id` int(10) unsigned NOT NULL,
  `platform_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emulator_downloads_emulator_id_platform_id_unique` (`emulator_id`,`platform_id`),
  KEY `emulator_downloads_platform_id_foreign` (`platform_id`),
  CONSTRAINT `emulator_downloads_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emulator_downloads_platform_id_foreign` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emulator_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulator_platforms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emulator_id` int(10) unsigned NOT NULL,
  `platform_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emulator_platforms_emulator_id_platform_id_unique` (`emulator_id`,`platform_id`),
  KEY `emulator_platforms_platform_id_foreign` (`platform_id`),
  CONSTRAINT `emulator_platforms_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emulator_platforms_platform_id_foreign` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `emulator_user_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulator_user_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emulator_id` int(10) unsigned NOT NULL,
  `client` varchar(80) NOT NULL,
  `minimum_allowed_version` varchar(32) DEFAULT NULL,
  `minimum_hardcore_version` varchar(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emulator_user_agents_emulator_id_foreign` (`emulator_id`),
  KEY `emulator_user_agents_client_index` (`client`),
  CONSTRAINT `emulator_user_agents_emulator_id_foreign` FOREIGN KEY (`emulator_id`) REFERENCES `emulators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emulators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emulators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `documentation_url` varchar(255) DEFAULT NULL,
  `download_url` varchar(255) DEFAULT NULL,
  `download_x64_url` varchar(255) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `order_column` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `can_debug_triggers` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `achievement_id` bigint(20) unsigned NOT NULL,
  `source_achievement_id` bigint(20) unsigned DEFAULT NULL,
  `active_from` date DEFAULT NULL,
  `active_until` date DEFAULT NULL,
  `decorator` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_achievements_achievement_id_foreign` (`achievement_id`),
  KEY `event_achievements_source_achievement_id_index` (`source_achievement_id`),
  KEY `event_achievements_active_from_index` (`active_from`),
  KEY `event_achievements_active_until_index` (`active_until`),
  CONSTRAINT `event_achievements_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `event_achievements_source_achievement_id_foreign` FOREIGN KEY (`source_achievement_id`) REFERENCES `Achievements` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_awards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `tier_index` int(11) NOT NULL,
  `label` varchar(40) NOT NULL,
  `points_required` int(11) NOT NULL,
  `image_asset_path` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_awards_event_id_tier_index_unique` (`event_id`,`tier_index`),
  CONSTRAINT `event_awards_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_game_id` bigint(20) unsigned NOT NULL,
  `image_asset_path` varchar(50) NOT NULL DEFAULT '/Images/000001.png',
  `active_from` date DEFAULT NULL,
  `active_until` date DEFAULT NULL,
  `gives_site_award` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_legacy_game_id_foreign` (`legacy_game_id`),
  CONSTRAINT `events_legacy_game_id_foreign` FOREIGN KEY (`legacy_game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forum_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL,
  `description` varchar(250) NOT NULL,
  `order_column` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forum_topic_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_topic_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `forum_topic_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `authorized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_authorized` tinyint(3) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `forum_topic_comments_forum_topic_id_index` (`forum_topic_id`),
  KEY `forum_topic_comments_created_at_index` (`created_at`),
  KEY `forum_topic_comments_author_id_created_at_index` (`author_id`,`created_at`),
  CONSTRAINT `forum_topic_comments_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `forum_topic_comments_forum_topic_id_foreign` FOREIGN KEY (`forum_topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forum_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_topics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `pinned_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latest_comment_id` bigint(20) unsigned DEFAULT NULL,
  `required_permissions` smallint(6) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `forum_topics_created_at_index` (`created_at`),
  KEY `idx_permissions_deleted_latest` (`required_permissions`,`deleted_at`,`latest_comment_id`),
  KEY `forum_topics_forum_id_index` (`forum_id`),
  KEY `forum_topics_author_id_foreign` (`author_id`),
  CONSTRAINT `forum_topics_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `forum_topics_forum_id_foreign` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forums` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `forumable_model` varchar(255) DEFAULT NULL,
  `forumable_id` bigint(20) unsigned DEFAULT NULL,
  `forum_category_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(250) NOT NULL,
  `latest_comment_id` bigint(20) unsigned DEFAULT NULL,
  `order_column` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forums_forumable_unique` (`forumable_model`,`forumable_id`),
  KEY `forums_forum_category_id_index` (`forum_category_id`),
  CONSTRAINT `forums_forum_category_id_foreign` FOREIGN KEY (`forum_category_id`) REFERENCES `forum_categories` (`id`) ON DELETE SET NULL
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
  `compatibility_tester_id` bigint(20) unsigned DEFAULT NULL,
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
  KEY `game_hashes_compatibility_tester_id_foreign` (`compatibility_tester_id`),
  CONSTRAINT `game_hashes_compatibility_tester_id_foreign` FOREIGN KEY (`compatibility_tester_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `game_hashes_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_hashes_system_id_foreign` FOREIGN KEY (`system_id`) REFERENCES `Console` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_hashes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_recent_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_recent_players` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `rich_presence` text DEFAULT NULL,
  `rich_presence_updated_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_recent_players_game_id_user_id_unique` (`game_id`,`user_id`),
  KEY `idx_game_updated` (`game_id`,`rich_presence_updated_at`),
  KEY `game_recent_players_user_id_foreign` (`user_id`),
  CONSTRAINT `game_recent_players_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_recent_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_releases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) unsigned NOT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `released_at_granularity` varchar(255) DEFAULT NULL,
  `title` varchar(80) NOT NULL,
  `region` varchar(20) DEFAULT NULL,
  `is_canonical_game_title` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_releases_title_index` (`title`),
  KEY `game_releases_game_id_foreign` (`game_id`),
  CONSTRAINT `game_releases_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE
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
  UNIQUE KEY `game_set_games_game_set_id_game_id_unique` (`game_set_id`,`game_id`),
  KEY `game_set_games_game_id_foreign` (`game_id`),
  CONSTRAINT `game_set_games_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `game_set_games_game_set_id_foreign` FOREIGN KEY (`game_set_id`) REFERENCES `game_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_set_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_set_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_game_set_id` bigint(20) unsigned NOT NULL,
  `child_game_set_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_set_links_parent_game_set_id_child_game_set_id_unique` (`parent_game_set_id`,`child_game_set_id`),
  KEY `game_set_links_parent_game_set_id_index` (`parent_game_set_id`),
  KEY `game_set_links_child_game_set_id_index` (`child_game_set_id`),
  CONSTRAINT `game_set_links_child_game_set_id_foreign` FOREIGN KEY (`child_game_set_id`) REFERENCES `game_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_set_links_parent_game_set_id_foreign` FOREIGN KEY (`parent_game_set_id`) REFERENCES `game_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_set_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_set_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `game_set_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission` enum('view','update') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_set_roles_game_set_id_role_id_permission_unique` (`game_set_id`,`role_id`,`permission`),
  KEY `game_set_roles_game_set_id_permission_index` (`game_set_id`,`permission`),
  KEY `game_set_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `game_set_roles_game_set_id_foreign` FOREIGN KEY (`game_set_id`) REFERENCES `game_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_set_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `title` varchar(80) DEFAULT NULL,
  `image_asset_path` varchar(50) DEFAULT '/Images/000001.png',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `definition` text DEFAULT NULL,
  `has_mature_content` tinyint(1) NOT NULL DEFAULT 0,
  `game_id` bigint(20) unsigned DEFAULT NULL,
  `forum_topic_id` bigint(20) unsigned DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_sets_user_id_index` (`user_id`),
  KEY `game_sets_game_id_foreign` (`game_id`),
  KEY `game_sets_forum_topic_id_index` (`forum_topic_id`),
  CONSTRAINT `game_sets_forum_topic_id_foreign` FOREIGN KEY (`forum_topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `game_sets_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
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
  KEY `idx_recent_entries` (`deleted_at`,`updated_at`,`leaderboard_id`),
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
  `sent_by_id` bigint(20) unsigned DEFAULT NULL,
  `Title` text NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `Unread` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `messages_unread_index` (`Unread`),
  KEY `messages_thread_id_foreign` (`thread_id`),
  KEY `messages_author_id_foreign` (`author_id`),
  KEY `messages_sent_by_id_foreign` (`sent_by_id`),
  CONSTRAINT `messages_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `messages_sent_by_id_foreign` FOREIGN KEY (`sent_by_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
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
DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `lead` text DEFAULT NULL,
  `body` text NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `image_asset_path` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `publish_at` timestamp NULL DEFAULT NULL,
  `unpublish_at` timestamp NULL DEFAULT NULL,
  `pinned_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_user_id_foreign` (`user_id`),
  CONSTRAINT `news_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
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
DROP TABLE IF EXISTS `platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platforms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `execution_environment` varchar(255) DEFAULT NULL,
  `order_column` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_achievement_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_achievement_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `achievement_set_id` bigint(20) unsigned NOT NULL,
  `achievements_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_softcore` int(10) unsigned DEFAULT NULL,
  `completion_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `time_taken_hardcore` int(11) DEFAULT NULL,
  `completion_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates`)),
  `completion_dates_hardcore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completion_dates_hardcore`)),
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_hardcore_at` timestamp NULL DEFAULT NULL,
  `last_unlock_at` timestamp NULL DEFAULT NULL,
  `last_unlock_hardcore_at` timestamp NULL DEFAULT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `points_hardcore` int(10) unsigned DEFAULT NULL,
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
  `achievements_total` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL,
  `achievements_unlocked_softcore` int(10) unsigned DEFAULT NULL,
  `all_achievements_total` int(11) DEFAULT NULL,
  `all_achievements_unlocked` int(11) DEFAULT NULL,
  `all_achievements_unlocked_hardcore` int(11) DEFAULT NULL,
  `all_points_total` int(11) DEFAULT NULL,
  `all_points` int(11) DEFAULT NULL,
  `all_points_hardcore` int(11) DEFAULT NULL,
  `all_points_weighted` int(11) DEFAULT NULL,
  `completion_percentage` decimal(10,9) unsigned DEFAULT NULL,
  `completion_percentage_hardcore` decimal(10,9) unsigned DEFAULT NULL,
  `last_played_at` timestamp NULL DEFAULT NULL,
  `playtime_total` int(11) DEFAULT NULL,
  `time_to_beat` int(11) DEFAULT NULL,
  `time_to_beat_hardcore` int(11) DEFAULT NULL,
  `time_taken` bigint(20) unsigned DEFAULT NULL,
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
  `points_total` int(10) unsigned DEFAULT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `points_hardcore` int(10) unsigned DEFAULT NULL,
  `points_weighted` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_games_user_id_game_id_unique` (`user_id`,`game_id`),
  KEY `player_games_created_at_index` (`created_at`),
  KEY `player_games_game_id_user_id_index` (`game_id`,`user_id`),
  KEY `player_games_game_id_achievements_unlocked_index` (`game_id`,`achievements_unlocked`),
  KEY `player_games_game_id_achievements_unlocked_hardcore_index` (`game_id`,`achievements_unlocked_hardcore`),
  KEY `player_games_game_id_achievements_unlocked_softcore_index` (`game_id`,`achievements_unlocked_softcore`),
  KEY `player_games_suggestions_index` (`user_id`,`achievements_unlocked`,`achievements_total`,`game_id`),
  CONSTRAINT `player_games_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `GameData` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `player_games_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_progress_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_progress_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `initiated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `type_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_progress_resets_user_id_type_created_at_index` (`user_id`,`type`,`created_at`),
  KEY `player_progress_resets_user_id_type_type_id_created_at_index` (`user_id`,`type`,`type_id`,`created_at`),
  KEY `player_progress_resets_initiated_by_user_id_foreign` (`initiated_by_user_id`),
  CONSTRAINT `player_progress_resets_initiated_by_user_id_foreign` FOREIGN KEY (`initiated_by_user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `player_progress_resets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int(10) unsigned NOT NULL,
  `period` mediumint(8) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(255) NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `triggers_triggerable_type_triggerable_id_version_unique` (`triggerable_type`,`triggerable_id`,`version`),
  KEY `triggers_triggerable_type_triggerable_id_index` (`triggerable_type`,`triggerable_id`),
  KEY `triggers_user_id_foreign` (`user_id`),
  KEY `triggers_parent_id_index` (`parent_id`),
  CONSTRAINT `triggers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `triggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `triggers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unranked_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unranked_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `unranked_users_user_id_index` (`user_id`),
  CONSTRAINT `unranked_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `user_game_achievement_set_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_game_achievement_set_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `game_achievement_set_id` bigint(20) unsigned NOT NULL,
  `opted_in` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_gasp` (`user_id`,`game_achievement_set_id`),
  KEY `fk_user_gasp_game_ach_set_id` (`game_achievement_set_id`),
  CONSTRAINT `fk_user_gasp_game_ach_set_id` FOREIGN KEY (`game_achievement_set_id`) REFERENCES `game_achievement_sets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_gasp_user_id` FOREIGN KEY (`user_id`) REFERENCES `UserAccounts` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_usernames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_usernames` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `denied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_usernames_username_index` (`username`),
  KEY `user_usernames_user_id_foreign` (`user_id`),
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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2024_06_18_000000_update_gamedata_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2024_05_25_000001_update_player_games_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2024_07_03_000000_update_codenotes_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2024_08_24_000000_add_forum_indexes',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2024_06_23_000000_update_useraccounts_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2024_08_10_000000_create_game_set_links_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2023_06_07_000001_create_pulse_tables',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2024_09_08_000000_update_emulators_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2024_09_11_000000_add_datatable_indexes',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2024_09_12_000000_update_gamedata_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2024_09_27_000000_create_user_game_achievement_set_preferences_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2024_10_12_000000_update_achievement_set_game_hashes_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2024_10_21_000000_create_event_achievements_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2024_11_02_000000_update_useraccounts_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2024_11_15_000000_create_emulator_user_agents_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2024_09_07_000000_update_achievement_authors_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2024_12_15_000000_update_gamedata_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2024_12_14_000000_update_game_sets_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2024_12_17_000000_drop_votes_and_rating_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2024_12_17_000001_update_news_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2024_12_16_000000_update_forumcategory_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2024_12_16_000001_update_forum_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2024_12_16_000002_update_forumtopic_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2024_12_16_000003_update_forumtopiccomment_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2024_12_18_000000_update_game_sets_tables',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2024_12_22_000000_update_game_sets_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2024_12_25_000000_update_leaderboard_entries_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_01_06_000000_create_events_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2024_12_24_000000_update_player_games_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_01_07_000000_denormalize_triggerables',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_01_07_000001_update_triggers_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_01_10_000000_create_event_awards_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_01_18_000000_update_news_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_01_20_000000_update_useraccounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_01_25_000000_update_player_games_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_01_29_000001_update_comments_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_01_17_000000_update_user_usernames_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_01_26_000000_update_useraccounts_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_01_29_000000_update_event_awards_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_03_07_000000_update_ticket_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_03_12_000000_update_events_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_03_16_000000_create_emulator_tables',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_04_03_000000_update_emulators_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_04_08_000000_update_emulator_tables',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_04_14_000000_update_achievement_sets_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_04_19_000000_drop_gamealternatives_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_04_19_000000_update_game_hashes_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_04_22_000000_create_achievement_maintainers_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_04_23_000000_update_messages_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_04_27_000000_create_achievement_maintainer_unlocks_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_05_02_000000_create_downloads_popularity_metrics_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_05_05_000000_update_player_achievement_sets',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_05_05_000001_update_achievement_sets',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_05_05_000002_update_player_games',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_05_05_000003_update_gamedata',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_05_11_000000_update_leaderboarddef_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_05_18_000000_update_events_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_05_19_000000_create_game_releases_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_05_31_000000_create_unranked_users_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_05_25_000000_create_game_set_roles_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_06_07_000000_update_event_achievements_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_06_14_000000_update_gamedata_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_06_27_000000_update_achievements_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_06_29_000000_create_player_progress_resets_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_07_04_000000_create_game_recent_players_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_07_13_000000_update_setrequests_table',47);
