# allow rp display to be up to 255 characters
ALTER TABLE `UserAccounts`
    CHANGE COLUMN `RichPresenceMsg` `RichPresenceMsg` VARCHAR(255) NULL DEFAULT NULL;
