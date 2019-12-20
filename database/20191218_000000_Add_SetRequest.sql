CREATE TABLE IF NOT EXISTS `SetRequest` (
  `User` varchar(32) COLLATE latin1_general_ci NOT NULL COMMENT 'Username',
  `GameID` int(10) unsigned NOT NULL COMMENT 'Unique Game ID',
  `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified via API',
  PRIMARY KEY (`User`,`GameID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;