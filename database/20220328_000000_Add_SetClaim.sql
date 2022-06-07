CREATE TABLE IF NOT EXISTS `SetClaim` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique claim ID',
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Username',
  `GameID` int(10) unsigned NOT NULL COMMENT 'Game ID for claim',
  `ClaimType` int(10) unsigned NOT NULL COMMENT '0 - Primary (counts against claim total), 1 - Collaboration (does not count against claim total)',
  `SetType` int(10) unsigned NOT NULL COMMENT '0 - New set, 1 - Revision',
  `Status` int(10) unsigned NOT NULL COMMENT '0 - Active, 1 - Complete, 2 - Dropped',
  `Extension` int(10) unsigned NOT NULL COMMENT 'Number of times the claim has been extended',
  `Special` int(10) unsigned NOT NULL COMMENT '0 - Standard claim, 1 - Own Revision, 2 - Free Rollout claim, 3 - Furutre release approved. >=1 does not count against claim count',
  `Created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim was made',
  `Finished` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim is completed, dropped or will expire',
  `Updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when the claim was last modified',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;