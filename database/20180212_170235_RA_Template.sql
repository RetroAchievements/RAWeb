-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.6.38 - MySQL Community Server (GPL)
-- Server OS:                    Linux
-- HeidiSQL Version:             9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table RACore.Achievements
CREATE TABLE IF NOT EXISTS `Achievements` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Achievement ID',
  `GameID` int(10) unsigned NOT NULL COMMENT 'FK into GameData',
  `Title` varchar(64) COLLATE latin1_general_ci NOT NULL COMMENT 'Short title of the achievement. Displayed when achieved.',
  `Description` varchar(256) COLLATE latin1_general_ci NOT NULL COMMENT 'Text used to describe the achievement.',
  `MemAddr` varchar(1024) COLLATE latin1_general_ci NOT NULL COMMENT 'Memory To Check',
  `Progress` varchar(256) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'How to calculate progress indicators',
  `ProgressMax` varchar(256) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'How to calculate progress indicator 2',
  `ProgressFormat` varchar(50) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'How to calculate progress indicators (Formatting)',
  `Points` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT 'Amount of RAPoints to award!',
  `Flags` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT 'Bits: 1=active, 2=core, 3=unofficial',
  `Author` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Author''s Username',
  `DateCreated` timestamp NULL DEFAULT NULL COMMENT 'Timestamp for when this was added',
  `DateModified` timestamp NULL DEFAULT NULL COMMENT 'Timestamp for when this was last modified',
  `VotesPos` smallint(6) unsigned NOT NULL DEFAULT '0',
  `VotesNeg` smallint(6) unsigned NOT NULL DEFAULT '0',
  `BadgeName` varchar(8) COLLATE latin1_general_ci DEFAULT '00001' COMMENT 'Filename (append .png) for the 64x64 badge for this element.',
  `DisplayOrder` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Display order to show achievements in',
  `AssocVideo` varchar(256) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Associated embedded youtube video',
  `TrueRatio` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GameID` (`GameID`),
  KEY `Author` (`Author`),
  KEY `Points` (`Points`),
  KEY `TrueRatio` (`TrueRatio`)
) ENGINE=InnoDB AUTO_INCREMENT=57656 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Main Test Table';

-- Data exporting was unselected.
-- Dumping structure for table RACore.Activity
CREATE TABLE IF NOT EXISTS `Activity` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastupdate` timestamp NULL DEFAULT NULL COMMENT 'used to test for recent behaviour duplicates',
  `activitytype` smallint(6) NOT NULL COMMENT '0=unknown;1=achievement;2=login;3=playgame;4=uploadach',
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'username',
  `data` varchar(20) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'misc data, such as achievement ID',
  `data2` varchar(12) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'additional data, i.e. leaderboards score',
  PRIMARY KEY (`ID`),
  KEY `User` (`User`),
  KEY `data` (`data`),
  KEY `activitytype` (`activitytype`),
  KEY `timestamp` (`timestamp`),
  KEY `lastupdate` (`lastupdate`),
  KEY `ID` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7853905 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.ArticleTypeDimension
CREATE TABLE IF NOT EXISTS `ArticleTypeDimension` (
  `ArticleTypeID` tinyint(3) unsigned NOT NULL,
  `ArticleType` varchar(50) COLLATE latin1_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.Awarded
CREATE TABLE IF NOT EXISTS `Awarded` (
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Change to UserID?',
  `AchievementID` int(11) NOT NULL,
  `Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `HardcoreMode` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`User`,`AchievementID`,`HardcoreMode`),
  KEY `User` (`User`),
  KEY `AchievementID` (`AchievementID`),
  KEY `Date` (`Date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.CodeNotes
CREATE TABLE IF NOT EXISTS `CodeNotes` (
  `GameID` int(10) unsigned NOT NULL COMMENT 'FK to GameData',
  `Address` int(10) unsigned NOT NULL COMMENT 'Read this as if it were hex, or it won''t make sense.',
  `AuthorID` int(10) unsigned NOT NULL COMMENT 'FK to UserAccounts',
  `Note` varchar(1024) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`GameID`,`Address`),
  KEY `GameID` (`GameID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='A shared note about a small block of memory';

-- Data exporting was unselected.
-- Dumping structure for table RACore.Comment
CREATE TABLE IF NOT EXISTS `Comment` (
  `ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'UID',
  `ArticleType` tinyint(3) unsigned NOT NULL COMMENT 'FK to ArticleTypeDimension',
  `ArticleID` int(10) unsigned NOT NULL COMMENT 'FK to Activity',
  `UserID` int(10) unsigned NOT NULL COMMENT 'FK to UserAccounts',
  `Payload` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `Submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Edited` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ArticleID` (`ArticleID`)
) ENGINE=InnoDB AUTO_INCREMENT=49963 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.Console
CREATE TABLE IF NOT EXISTS `Console` (
  `ID` tinyint(4) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Console Types';

-- Data exporting was unselected.
-- Dumping structure for table RACore.EmailConfirmations
CREATE TABLE IF NOT EXISTS `EmailConfirmations` (
  `User` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `EmailCookie` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `Expires` date NOT NULL,
  KEY `EmailCookie` (`EmailCookie`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.Forum
CREATE TABLE IF NOT EXISTS `Forum` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CategoryID` varchar(50) COLLATE latin1_general_ci NOT NULL COMMENT 'FK to ForumCategory',
  `Title` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `Description` varchar(250) COLLATE latin1_general_ci NOT NULL,
  `LatestCommentID` int(10) unsigned DEFAULT NULL COMMENT 'FK to ForumTopicComment',
  `DisplayOrder` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CategoryID` (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Represents entries of a single forum object';

-- Data exporting was unselected.
-- Dumping structure for table RACore.ForumCategory
CREATE TABLE IF NOT EXISTS `ForumCategory` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(250) COLLATE latin1_general_ci NOT NULL COMMENT 'Category displayable name',
  `Description` varchar(250) COLLATE latin1_general_ci NOT NULL COMMENT 'Small description',
  `DisplayOrder` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.ForumTopic
CREATE TABLE IF NOT EXISTS `ForumTopic` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ForumID` int(10) unsigned NOT NULL COMMENT 'FK to Forum',
  `Title` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `Author` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `AuthorID` int(10) unsigned NOT NULL COMMENT 'FK to UserAccounts',
  `DateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LatestCommentID` int(10) unsigned NOT NULL COMMENT 'FK to ForumTopicComment',
  `RequiredPermissions` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ForumID` (`ForumID`)
) ENGINE=InnoDB AUTO_INCREMENT=6140 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='User-created topics of discussion';

-- Data exporting was unselected.
-- Dumping structure for table RACore.ForumTopicComment
CREATE TABLE IF NOT EXISTS `ForumTopicComment` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ForumTopicID` int(10) unsigned NOT NULL COMMENT 'FK to ForumTopic',
  `Payload` varchar(65000) COLLATE latin1_general_ci NOT NULL,
  `Author` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `AuthorID` int(10) unsigned NOT NULL COMMENT 'FK to UserAccounts',
  `DateCreated` timestamp NULL DEFAULT NULL,
  `DateModified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Authorised` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ForumTopicID` (`ForumTopicID`),
  KEY `DateCreated` (`DateCreated`)
) ENGINE=InnoDB AUTO_INCREMENT=30712 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='User comment reply to a ForumTopic=';

-- Data exporting was unselected.
-- Dumping structure for table RACore.Friends
CREATE TABLE IF NOT EXISTS `Friends` (
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'TBD: Convert to ID',
  `Friend` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'TBD: Convert to ID',
  `Friendship` tinyint(4) NOT NULL COMMENT '0 = unknown/unconfirmed. 1 = accepted. -1 = blocked.',
  KEY `User` (`User`),
  KEY `Friend` (`Friend`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.GameAlternatives
CREATE TABLE IF NOT EXISTS `GameAlternatives` (
  `gameID` int(10) unsigned DEFAULT NULL,
  `gameIDAlt` int(10) unsigned DEFAULT NULL,
  UNIQUE KEY `gameID_gameIDAlt` (`gameID`,`gameIDAlt`),
  KEY `gameID` (`gameID`),
  KEY `gameIDAlt` (`gameIDAlt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.GameData
CREATE TABLE IF NOT EXISTS `GameData` (
  `ID` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Auto-assigned ID for this game',
  `Title` varchar(80) COLLATE latin1_general_ci NOT NULL COMMENT 'Given Title for this game (user supplied)',
  `ConsoleID` tinyint(4) NOT NULL COMMENT 'FK to Console',
  `ForumTopicID` int(10) DEFAULT NULL COMMENT 'FK to ForumTopic, Official Forum Topic ID',
  `Flags` int(10) DEFAULT NULL,
  `ImageIcon` varchar(50) COLLATE latin1_general_ci DEFAULT '/Images/000001.png',
  `ImageTitle` varchar(50) COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
  `ImageIngame` varchar(50) COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
  `ImageBoxArt` varchar(50) COLLATE latin1_general_ci DEFAULT '/Images/000002.png',
  `Publisher` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `Developer` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `Genre` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `Released` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `IsFinal` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `RichPresencePatch` varchar(2500) COLLATE latin1_general_ci DEFAULT NULL,
  `TotalTruePoints` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TitleConsole Pair` (`Title`,`ConsoleID`),
  KEY `ConsoleID` (`ConsoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=11660 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.GameHashLibrary
CREATE TABLE IF NOT EXISTS `GameHashLibrary` (
  `MD5` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Unique checksum for this ROM',
  `GameID` int(10) unsigned NOT NULL COMMENT 'Game that the given MD5 forwards to',
  PRIMARY KEY (`MD5`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.LeaderboardDef
CREATE TABLE IF NOT EXISTS `LeaderboardDef` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Leaderboard ID',
  `GameID` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to GameData, Game to which it refers',
  `Mem` varchar(2000) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT 'Memory to watch to submit score',
  `Format` varchar(50) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT 'How to display the score',
  `Title` varchar(80) CHARACTER SET latin1 NOT NULL DEFAULT 'Leaderboard Title',
  `Description` varchar(250) CHARACTER SET latin1 NOT NULL DEFAULT 'Short Description of this Leaderboard',
  `LowerIsBetter` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'What default sort of this leaderboard',
  `DisplayOrder` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GameID` (`GameID`)
) ENGINE=InnoDB AUTO_INCREMENT=3616 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.LeaderboardEntry
CREATE TABLE IF NOT EXISTS `LeaderboardEntry` (
  `LeaderboardID` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to LeaderboardDef',
  `UserID` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to UserAccounts',
  `Score` int(10) NOT NULL DEFAULT '0' COMMENT 'Score Entry. Always used as signed int',
  `DateSubmitted` datetime NOT NULL COMMENT 'Last Submitted',
  PRIMARY KEY (`LeaderboardID`,`UserID`),
  KEY `LeaderboardID` (`LeaderboardID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.Messages
CREATE TABLE IF NOT EXISTS `Messages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserTo` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `UserFrom` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `Title` text COLLATE latin1_general_ci NOT NULL,
  `Payload` text COLLATE latin1_general_ci NOT NULL,
  `TimeSent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Unread` tinyint(4) NOT NULL,
  `Type` int(11) NOT NULL COMMENT 'Currently Unused',
  PRIMARY KEY (`ID`),
  KEY `UserTo` (`UserTo`),
  KEY `Unread` (`Unread`)
) ENGINE=InnoDB AUTO_INCREMENT=39778 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.News
CREATE TABLE IF NOT EXISTS `News` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Title` varchar(500) COLLATE latin1_general_ci NOT NULL COMMENT 'Title of article',
  `Payload` varchar(10000) COLLATE latin1_general_ci NOT NULL COMMENT 'Content',
  `Author` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `Link` varchar(1024) COLLATE latin1_general_ci DEFAULT NULL,
  `Image` varchar(1024) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=404 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table RACore.PlaylistVideo
CREATE TABLE IF NOT EXISTS `PlaylistVideo` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) NOT NULL DEFAULT '0',
  `Author` varchar(32) NOT NULL DEFAULT '0',
  `Link` varchar(100) NOT NULL DEFAULT '0',
  `Added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.Rating
CREATE TABLE IF NOT EXISTS `Rating` (
  `User` varchar(255) NOT NULL,
  `RatingObjectType` smallint(6) NOT NULL,
  `RatingID` smallint(6) NOT NULL,
  `RatingValue` smallint(6) NOT NULL,
  PRIMARY KEY (`User`,`RatingObjectType`,`RatingID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.ScoreHistory
CREATE TABLE IF NOT EXISTS `ScoreHistory` (
  `SnapshotTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `User` varchar(50) NOT NULL,
  `Score` int(10) unsigned NOT NULL,
  PRIMARY KEY (`SnapshotTime`,`User`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.SiteAwards
CREATE TABLE IF NOT EXISTS `SiteAwards` (
  `AwardDate` datetime NOT NULL,
  `User` varchar(50) NOT NULL COMMENT 'The User who has earned an award',
  `AwardType` int(10) NOT NULL,
  `AwardData` int(10) DEFAULT NULL COMMENT 'A value to associate: type-dependent.',
  `AwardDataExtra` int(10) NOT NULL DEFAULT '0',
  UNIQUE KEY `User_AwardType` (`User`,`AwardData`,`AwardType`,`AwardDataExtra`),
  KEY `User` (`User`),
  KEY `AwardType` (`AwardType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.StaticData
CREATE TABLE IF NOT EXISTS `StaticData` (
  `NumAchievements` int(10) unsigned NOT NULL,
  `NumAwarded` int(10) unsigned NOT NULL,
  `NumGames` int(10) unsigned NOT NULL,
  `NumRegisteredUsers` int(10) unsigned NOT NULL,
  `TotalPointsEarned` int(10) unsigned NOT NULL,
  `LastAchievementEarnedID` int(10) unsigned NOT NULL,
  `LastAchievementEarnedByUser` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `LastAchievementEarnedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LastRegisteredUser` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `LastRegisteredUserAt` timestamp NULL DEFAULT NULL,
  `LastUpdatedGameID` int(10) unsigned NOT NULL,
  `LastUpdatedAchievementID` int(10) unsigned NOT NULL,
  `LastCreatedGameID` int(10) unsigned NOT NULL,
  `LastCreatedAchievementID` int(10) unsigned NOT NULL,
  `NextGameToScan` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'fk to GameData',
  `NextUserIDToScan` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'fk to UserAccounts',
  `Event_AOTW_AchievementID` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'fk to Achievements',
  `Event_AOTW_ForumID` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'fk to ForumTopic'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Collection of static data, useful for one-time operation';

-- Data exporting was unselected.
-- Dumping structure for table RACore.Ticket
CREATE TABLE IF NOT EXISTS `Ticket` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `AchievementID` int(10) unsigned NOT NULL,
  `ReportedByUserID` int(10) unsigned NOT NULL,
  `ReportType` tinyint(3) unsigned NOT NULL,
  `ReportNotes` varchar(10000) NOT NULL,
  `ReportedAt` timestamp NULL DEFAULT NULL,
  `ResolvedAt` timestamp NULL DEFAULT NULL,
  `ResolvedByUserID` int(10) unsigned DEFAULT NULL,
  `ReportState` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1=submitted,2=resolved,3=declined',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `AchievementID_ReportedByUserID` (`AchievementID`,`ReportedByUserID`),
  KEY `ReportedAt` (`ReportedAt`)
) ENGINE=InnoDB AUTO_INCREMENT=11558 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
-- Dumping structure for table RACore.UserAccounts
CREATE TABLE IF NOT EXISTS `UserAccounts` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier for this user',
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Username (32 chars)',
  `SaltedPass` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Salted password (32 MD5)',
  `EmailAddress` varchar(64) COLLATE latin1_general_ci NOT NULL COMMENT 'Plaintext Email Address (64 chars)',
  `Permissions` tinyint(4) NOT NULL COMMENT 'Permissions: -1=banned, 0=unconfirmedemail, 1=user, 2=dev (commit to test db), 3=sudev (commit/manage achievements), 4=admin',
  `RAPoints` int(11) NOT NULL COMMENT 'Gamerscore :P',
  `fbUser` bigint(20) NOT NULL COMMENT 'FBUser ID',
  `fbPrefs` smallint(6) DEFAULT NULL COMMENT 'Preferences for FB Cross-posting',
  `cookie` varchar(20) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Random string to be matched against the user for validation.',
  `appToken` varchar(20) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Token used by the app',
  `appTokenExpiry` datetime DEFAULT NULL COMMENT 'Expiry of token used by the app',
  `websitePrefs` smallint(5) unsigned DEFAULT '0',
  `LastLogin` timestamp NULL DEFAULT NULL,
  `LastActivityID` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'FK to Activity',
  `Motto` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `ContribCount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'The Number of awarded achievements that this user was the author of',
  `ContribYield` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'The total points allocated for achievements that this user has been the author of',
  `APIKey` varchar(50) COLLATE latin1_general_ci DEFAULT NULL COMMENT 'Random 32 char, set on account create, used for unique API access.',
  `APIUses` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of API Calls made',
  `LastGameID` int(10) unsigned NOT NULL DEFAULT '0',
  `RichPresenceMsg` varchar(100) COLLATE latin1_general_ci NOT NULL DEFAULT 'Unknown',
  `RichPresenceMsgDate` datetime DEFAULT NULL COMMENT 'Time of Update of RichPresenceMsg',
  `ManuallyVerified` tinyint(3) unsigned DEFAULT '0' COMMENT 'If 0, cannot post directly to forums without manual permission',
  `UnreadMessageCount` int(11) unsigned DEFAULT NULL,
  `TrueRAPoints` int(10) unsigned DEFAULT NULL,
  `UserWallActive` bit(1) NOT NULL DEFAULT b'1' COMMENT 'Allow Posting to user wall',
  `PasswordResetToken` varchar(32) COLLATE latin1_general_ci DEFAULT NULL,
  `Untracked` bit(1) NOT NULL DEFAULT b'0' COMMENT 'Untracked users are considered as having cheated.',
  PRIMARY KEY (`ID`,`User`),
  KEY `RAPoints` (`RAPoints`),
  KEY `ID` (`ID`),
  KEY `User` (`User`),
  KEY `LastActivityID` (`LastActivityID`),
  KEY `TrueRAPoints` (`TrueRAPoints`)
) ENGINE=InnoDB AUTO_INCREMENT=62866 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Dumping structure for table RACore.Votes
CREATE TABLE IF NOT EXISTS `Votes` (
  `User` varchar(50) COLLATE latin1_general_ci NOT NULL,
  `AchievementID` int(10) unsigned NOT NULL,
  `Vote` tinyint(4) NOT NULL,
  PRIMARY KEY (`User`,`AchievementID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Record of votes for achievements that are cast by users';

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
