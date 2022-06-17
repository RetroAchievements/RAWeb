/*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
/*!40103 SET TIME_ZONE = '+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Achievements`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Achievement ID',
    `GameID` int unsigned NOT NULL COMMENT 'FK into GameData',
    `Title` varchar(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Short title of the achievement. Displayed when achieved.',
    `Description` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `MemAddr` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Memory To Check',
    `Progress` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `ProgressMax` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `ProgressFormat` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'How to calculate progress indicators (Formatting)',
    `Points` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Amount of RAPoints to award!',
    `Flags` tinyint unsigned NOT NULL DEFAULT '5' COMMENT 'Bits: 1=active, 2=core, 3=unofficial',
    `Author` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Author''s Username',
    `DateCreated` timestamp NULL DEFAULT NULL COMMENT 'Timestamp for when this was added',
    `DateModified` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified via API',
    `VotesPos` smallint unsigned NOT NULL DEFAULT '0',
    `VotesNeg` smallint unsigned NOT NULL DEFAULT '0',
    `BadgeName` varchar(8) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT '00001' COMMENT 'Filename (append .png) for the 64x64 badge for this element.',
    `DisplayOrder` smallint NOT NULL DEFAULT '0' COMMENT 'Display order to show achievements in',
    `AssocVideo` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `TrueRatio` int unsigned NOT NULL DEFAULT '0',
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified',
    PRIMARY KEY (`ID`),
    KEY `GameID` (`GameID`),
    KEY `Author` (`Author`),
    KEY `Points` (`Points`),
    KEY `TrueRatio` (`TrueRatio`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Main Test Table';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Activity`
(
    `ID` int NOT NULL AUTO_INCREMENT,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `lastupdate` timestamp NULL DEFAULT NULL COMMENT 'used to test for recent behaviour duplicates',
    `activitytype` smallint NOT NULL COMMENT '0=unknown;1=achievement;2=login;3=playgame;4=uploadach',
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'username',
    `data` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'misc data, such as achievement ID',
    `data2` varchar(12) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'additional data, i.e. leaderboards score',
    PRIMARY KEY (`ID`),
    KEY `User` (`User`),
    KEY `data` (`data`),
    KEY `activitytype` (`activitytype`),
    KEY `timestamp` (`timestamp`),
    KEY `lastupdate` (`lastupdate`),
    KEY `ID` (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ArticleTypeDimension`
(
    `ArticleTypeID` tinyint unsigned NOT NULL,
    `ArticleType` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    PRIMARY KEY (`ArticleTypeID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Awarded`
(
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Change to UserID?',
    `AchievementID` int NOT NULL,
    `Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `HardcoreMode` tinyint unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`User`, `AchievementID`, `HardcoreMode`),
    KEY `User` (`User`),
    KEY `AchievementID` (`AchievementID`),
    KEY `Date` (`Date`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Chat`
(
    `ID` int NOT NULL AUTO_INCREMENT,
    `Submitted` datetime NOT NULL,
    `User` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Message` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    PRIMARY KEY (`ID`),
    KEY `Submitted` (`Submitted`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `CodeNotes`
(
    `GameID` int unsigned NOT NULL COMMENT 'FK to GameData',
    `Address` int unsigned NOT NULL COMMENT 'Read this as if it were hex, or it won''t make sense.',
    `AuthorID` int unsigned NOT NULL COMMENT 'FK to UserAccounts',
    `Note` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`GameID`, `Address`),
    KEY `GameID` (`GameID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='A shared note about a small block of memory';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Comment`
(
    `ID` int NOT NULL AUTO_INCREMENT COMMENT 'UID',
    `ArticleType` tinyint unsigned NOT NULL COMMENT 'FK to ArticleTypeDimension',
    `ArticleID` int unsigned NOT NULL COMMENT 'FK to Activity',
    `UserID` int unsigned NOT NULL COMMENT 'FK to UserAccounts',
    `Payload` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Edited` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `ArticleID` (`ArticleID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Console`
(
    `ID` tinyint NOT NULL AUTO_INCREMENT,
    `Name` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Console Types';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `DeletedModels`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `ModelType` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `ModelID` int unsigned NOT NULL,
    `DeletedByUserID` int unsigned DEFAULT NULL,
    `Deleted` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `EmailConfirmations`
(
    `User` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `EmailCookie` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Expires` date NOT NULL,
    KEY `EmailCookie` (`EmailCookie`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Forum`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `CategoryID` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'FK to ForumCategory',
    `Title` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Description` varchar(250) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `LatestCommentID` int unsigned DEFAULT NULL COMMENT 'FK to ForumTopicComment',
    `DisplayOrder` int NOT NULL DEFAULT '0',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `CategoryID` (`CategoryID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Represents entries of a single forum object';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ForumCategory`
(
    `ID` int NOT NULL AUTO_INCREMENT,
    `Name` varchar(250) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Category displayable name',
    `Description` varchar(250) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Small description',
    `DisplayOrder` int unsigned NOT NULL DEFAULT '0',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ForumTopic`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `ForumID` int unsigned NOT NULL COMMENT 'FK to Forum',
    `Title` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Author` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `AuthorID` int unsigned NOT NULL COMMENT 'FK to UserAccounts',
    `DateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `LatestCommentID` int unsigned NOT NULL COMMENT 'FK to ForumTopicComment',
    `RequiredPermissions` smallint NOT NULL DEFAULT '0',
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `ForumID` (`ForumID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='User-created topics of discussion';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `ForumTopicComment`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `ForumTopicID` int unsigned NOT NULL COMMENT 'FK to ForumTopic',
    `Payload` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Author` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `AuthorID` int unsigned NOT NULL COMMENT 'FK to UserAccounts',
    `DateCreated` timestamp NULL DEFAULT NULL,
    `DateModified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `Authorised` tinyint unsigned DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `ForumTopicID` (`ForumTopicID`),
    KEY `DateCreated` (`DateCreated`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='User comment reply to a ForumTopic=';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Friends`
(
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'TBD: Convert to ID',
    `Friend` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'TBD: Convert to ID',
    `Friendship` tinyint NOT NULL COMMENT '0 = unknown/unconfirmed. 1 = accepted. -1 = blocked.',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `User` (`User`),
    KEY `Friend` (`Friend`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `GameAlternatives`
(
    `gameID` int unsigned DEFAULT NULL,
    `gameIDAlt` int unsigned DEFAULT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `gameID_gameIDAlt` (`gameID`, `gameIDAlt`),
    KEY `gameID` (`gameID`),
    KEY `gameIDAlt` (`gameIDAlt`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `GameData`
(
    `ID` int NOT NULL AUTO_INCREMENT COMMENT 'Auto-assigned ID for this game',
    `Title` varchar(80) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Given Title for this game (user supplied)',
    `ConsoleID` tinyint NOT NULL COMMENT 'FK to Console',
    `ForumTopicID` int DEFAULT NULL COMMENT 'FK to ForumTopic, Official Forum Topic ID',
    `Flags` int DEFAULT NULL,
    `ImageIcon` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT '/Images/000001.png',
    `ImageTitle` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
    `ImageIngame` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
    `ImageBoxArt` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
    `Publisher` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Developer` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Genre` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Released` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `IsFinal` tinyint unsigned NOT NULL DEFAULT '0',
    `RichPresencePatch` text CHARACTER SET latin1 COLLATE latin1_general_ci,
    `TotalTruePoints` int unsigned NOT NULL DEFAULT '0',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `TitleConsole Pair` (`Title`, `ConsoleID`),
    KEY `ConsoleID` (`ConsoleID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `GameHashLibrary`
(
    `MD5` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Unique checksum for this ROM',
    `GameID` int unsigned NOT NULL COMMENT 'Game that the given MD5 forwards to',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Name` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Labels` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    PRIMARY KEY (`MD5`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `LeaderboardDef`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Leaderboard ID',
    `GameID` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to GameData, Game to which it refers',
    `Mem` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Memory to watch to submit score',
    `Format` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '' COMMENT 'How to display the score',
    `Title` varchar(80) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'Leaderboard Title',
    `Description` varchar(250) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'Short Description of this Leaderboard',
    `LowerIsBetter` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'What default sort of this leaderboard',
    `DisplayOrder` int NOT NULL DEFAULT '0',
    `Author` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `GameID` (`GameID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `LeaderboardEntry`
(
    `LeaderboardID` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to LeaderboardDef',
    `UserID` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to UserAccounts',
    `Score` int NOT NULL DEFAULT '0' COMMENT 'Score Entry. Always used as signed int',
    `DateSubmitted` datetime NOT NULL COMMENT 'Last Submitted',
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`LeaderboardID`, `UserID`),
    KEY `LeaderboardID` (`LeaderboardID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Messages`
(
    `ID` int NOT NULL AUTO_INCREMENT,
    `UserTo` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `UserFrom` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Title` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `Payload` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `TimeSent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Unread` tinyint NOT NULL,
    `Type` int NOT NULL COMMENT 'Currently Unused',
    PRIMARY KEY (`ID`),
    KEY `UserTo` (`UserTo`),
    KEY `Unread` (`Unread`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `News`
(
    `ID` int NOT NULL AUTO_INCREMENT,
    `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Title` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Payload` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Content',
    `Author` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Link` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Image` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Rating`
(
    `User` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `RatingObjectType` smallint NOT NULL,
    `RatingID` smallint NOT NULL,
    `RatingValue` smallint NOT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`User`, `RatingObjectType`, `RatingID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `SetClaim`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique claim ID',
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Username',
    `GameID` int unsigned NOT NULL COMMENT 'Game ID for claim',
    `ClaimType` int unsigned NOT NULL COMMENT '0 - Primary (counts against claim total), 1 - Collaboration (does not count against claim total)',
    `SetType` int unsigned NOT NULL COMMENT '0 - New set, 1 - Revision',
    `Status` int unsigned NOT NULL COMMENT '0 - Active, 1 - Complete, 2 - Dropped',
    `Extension` int unsigned NOT NULL COMMENT 'Number of times the claim has been extended',
    `Special` int unsigned NOT NULL COMMENT '0 - Standard claim, 1 - Own Revision, 2 - Free Rollout claim, 3 - Future release approved. >=1 does not count against claim count',
    `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim was made',
    `Finished` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim is completed, dropped or will expire',
    `Updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim was last modified',
    PRIMARY KEY (`ID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `SetRequest`
(
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Username',
    `GameID` int unsigned NOT NULL COMMENT 'Unique Game ID',
    `Created` timestamp NULL DEFAULT NULL,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified via API',
    PRIMARY KEY (`User`, `GameID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `SiteAwards`
(
    `AwardDate` datetime NOT NULL,
    `User` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'The User who has earned an award',
    `AwardType` int NOT NULL,
    `AwardData` int DEFAULT NULL COMMENT 'A value to associate: type-dependent.',
    `AwardDataExtra` int NOT NULL DEFAULT '0',
    `DisplayOrder` smallint NOT NULL DEFAULT '0' COMMENT 'Display order to show site awards in',
    UNIQUE KEY `User_AwardType` (`User`, `AwardData`, `AwardType`, `AwardDataExtra`),
    KEY `User` (`User`),
    KEY `AwardType` (`AwardType`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `StaticData`
(
    `NumAchievements` int unsigned NOT NULL,
    `NumAwarded` int unsigned NOT NULL,
    `NumGames` int unsigned NOT NULL,
    `NumRegisteredUsers` int unsigned NOT NULL,
    `TotalPointsEarned` int unsigned NOT NULL,
    `LastAchievementEarnedID` int unsigned NOT NULL,
    `LastAchievementEarnedByUser` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `LastAchievementEarnedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `LastRegisteredUser` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `LastRegisteredUserAt` timestamp NULL DEFAULT NULL,
    `LastUpdatedGameID` int unsigned NOT NULL,
    `LastUpdatedAchievementID` int unsigned NOT NULL,
    `LastCreatedGameID` int unsigned NOT NULL,
    `LastCreatedAchievementID` int unsigned NOT NULL,
    `NextGameToScan` int unsigned NOT NULL DEFAULT '1' COMMENT 'fk to GameData',
    `NextUserIDToScan` int unsigned NOT NULL DEFAULT '1' COMMENT 'fk to UserAccounts',
    `Event_AOTW_AchievementID` int unsigned NOT NULL DEFAULT '1' COMMENT 'fk to Achievements',
    `Event_AOTW_ForumID` int unsigned NOT NULL DEFAULT '1' COMMENT 'fk to ForumTopic',
    `Event_AOTW_StartAt` timestamp NULL DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Collection of static data, useful for one-time operation';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Subscription`
(
    `SubjectType` enum ('ForumTopic','UserWall','GameTickets','GameWall','GameAchievements','Achievement') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Type of the Subscription Subject',
    `SubjectID` int unsigned NOT NULL COMMENT 'FK to the Subscription Subject',
    `UserID` int unsigned NOT NULL COMMENT 'FK to UserAccounts',
    `State` tinyint unsigned NOT NULL COMMENT 'Whether UserID is subscribed (1) or unsubscribed (0)',
    PRIMARY KEY (`SubjectType`, `SubjectID`, `UserID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Explicit user subscriptions';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Ticket`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT,
    `AchievementID` int unsigned NOT NULL,
    `ReportedByUserID` int unsigned NOT NULL,
    `ReportType` tinyint unsigned NOT NULL,
    `Hardcore` tinyint(1) DEFAULT NULL,
    `ReportNotes` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `ReportedAt` timestamp NULL DEFAULT NULL,
    `ResolvedAt` timestamp NULL DEFAULT NULL,
    `ResolvedByUserID` int unsigned DEFAULT NULL,
    `ReportState` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=submitted,2=resolved,3=declined',
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `AchievementID_ReportedByUserID` (`AchievementID`, `ReportedByUserID`),
    KEY `ReportedAt` (`ReportedAt`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `UserAccounts`
(
    `ID` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier for this user',
    `User` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Username (32 chars)',
    `Password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `SaltedPass` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Salted password (32 MD5)',
    `EmailAddress` varchar(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Plaintext Email Address (64 chars)',
    `Permissions` tinyint NOT NULL COMMENT 'Permissions: -1=banned, 0=unconfirmedemail, 1=user, 2=dev (commit to test db), 3=sudev (commit/manage achievements), 4=admin',
    `RAPoints` int NOT NULL COMMENT 'Gamerscore :P',
    `RASoftcorePoints` int NOT NULL,
    `fbUser` bigint NOT NULL COMMENT 'FBUser ID',
    `fbPrefs` smallint DEFAULT NULL COMMENT 'Preferences for FB Cross-posting',
    `cookie` varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Random string to be matched against the user for validation.',
    `appToken` varchar(60) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Token used by the app',
    `appTokenExpiry` datetime DEFAULT NULL COMMENT 'Expiry of token used by the app',
    `websitePrefs` smallint unsigned DEFAULT '0',
    `LastLogin` timestamp NULL DEFAULT NULL,
    `LastActivityID` int unsigned NOT NULL DEFAULT '0' COMMENT 'FK to Activity',
    `Motto` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '',
    `ContribCount` int unsigned NOT NULL DEFAULT '0' COMMENT 'The Number of awarded achievements that this user was the author of',
    `ContribYield` int unsigned NOT NULL DEFAULT '0' COMMENT 'The total points allocated for achievements that this user has been the author of',
    `APIKey` varchar(60) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Random 32 char, set on account create, used for unique API access.',
    `APIUses` int unsigned NOT NULL DEFAULT '0' COMMENT 'Number of API Calls made',
    `LastGameID` int unsigned NOT NULL DEFAULT '0',
    `RichPresenceMsg` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `RichPresenceMsgDate` datetime DEFAULT NULL COMMENT 'Time of Update of RichPresenceMsg',
    `ManuallyVerified` tinyint unsigned DEFAULT '0' COMMENT 'If 0, cannot post directly to forums without manual permission',
    `UnreadMessageCount` int unsigned DEFAULT NULL,
    `TrueRAPoints` int unsigned DEFAULT NULL,
    `UserWallActive` bit(1) NOT NULL DEFAULT b'1' COMMENT 'Allow Posting to user wall',
    `PasswordResetToken` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Untracked` bit(1) NOT NULL DEFAULT b'0' COMMENT 'Untracked users are considered as having cheated.',
    `email_backup` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `DeleteRequested` timestamp NULL DEFAULT NULL,
    `Deleted` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`ID`, `User`),
    UNIQUE KEY `User` (`User`) USING BTREE,
    KEY `ID` (`ID`),
    KEY `LastActivityID` (`LastActivityID`),
    KEY `RAPointsUntracked` (`RAPoints`, `Untracked`) USING BTREE,
    KEY `RASoftcorePointsUntracked` (`RASoftcorePoints`,`Untracked`) USING BTREE,
    KEY `UserUntracked` (`User`, `Untracked`) USING BTREE,
    KEY `TrueRAPointsUntracked` (`TrueRAPoints`, `Untracked`) USING BTREE,
    KEY `UntrackedRAPoints` (`Untracked`, `RAPoints`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `Votes`
(
    `User` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `AchievementID` int unsigned NOT NULL,
    `Vote` tinyint NOT NULL,
    `Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`User`, `AchievementID`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  COLLATE = latin1_general_ci COMMENT ='Record of votes for achievements that are cast by users';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE = @OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE = @OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;
