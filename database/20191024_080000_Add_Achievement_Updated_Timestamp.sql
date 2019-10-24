# Achievements
# touched on badge upload

# revert DateModified to previous behaviour where it only gets updated explicitly on api changes
ALTER TABLE `Achievements`
    CHANGE COLUMN `DateModified` `DateModified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified via API';

# add a dedicated Updated timestamp for changes that are relevant for synchronisation accuracy
ALTER TABLE `Achievements`
    ADD COLUMN `Updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp for when this was last modified';

UPDATE `Achievements` SET `Updated` = `DateModified` WHERE DateModified IS NOT NULL;
