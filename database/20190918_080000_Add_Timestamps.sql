--
-- Create/modify timestamp columns to be automatically populated by either creation or update timestamp.
-- Some insert statements had to be adjusted to make this work by explicitly specifying the columns.
-- This will allow to do synchronisation tasks incrementally in a consistent and performant way.
-- Clear Created columns after adding them - those would not be accurate - Updated are fine.
--

# Add a table for deletions

CREATE TABLE IF NOT EXISTS `DeletedModels` (
    ID              INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ModelType       VARCHAR(30)      NOT NULL,
    ModelID         INT(10) UNSIGNED NOT NULL,
    DeletedByUserID INT(10) UNSIGNED,
    Deleted         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

# Achievements
# touched on badge upload

ALTER TABLE `Achievements`
    CHANGE COLUMN `DateModified` `DateModified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified';

# Awarded
# has Date

# CodeNotes

ALTER TABLE `CodeNotes`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `CodeNotes`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# Console

ALTER TABLE `Console`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `Console`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# Comment
# has Submitted and Edited

# ForumCategory

ALTER TABLE `ForumCategory`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `ForumCategory`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# Forum

ALTER TABLE `Forum`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `Forum`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# ForumTopic
# has DateCreated for Created

ALTER TABLE `ForumTopic`
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

# ForumTopicComment
# has DateCreated, DateModified
# COALESCE DateCreated, DateModified

# Friends

ALTER TABLE `Friends`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `Friends`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# GameData
# touched on pic upload

ALTER TABLE `GameData`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `GameData`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# GameAlternatives Created

ALTER TABLE `GameAlternatives`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `GameAlternatives`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# GameHashLibrary Created

ALTER TABLE `GameHashLibrary`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE `GameHashLibrary`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# LeaderboardDef Updated

ALTER TABLE `LeaderboardDef`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `LeaderboardDef`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# LeaderboardEntry DateSubmitted -> default current, check insert statement

ALTER TABLE `LeaderboardEntry`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE `LeaderboardEntry`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# Messages
# has TimeSent for Created

# News
# has Timestamp for Created

ALTER TABLE `News`
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

# Rating

ALTER TABLE `Rating`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `Rating`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# SiteAwards
# has AwardDate for Created and Updated

# Ticket
# has ReportedAt for Created

ALTER TABLE `Ticket`
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

# UserAccounts

ALTER TABLE `UserAccounts`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

UPDATE `UserAccounts`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;

# Votes

ALTER TABLE `Votes`
    ADD COLUMN `Created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `Updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE `Votes`
SET `Created` = NULL
WHERE `Created` IS NOT NULL;
