--
-- This script will create a new field called "DisplayOrder" in the siteawards table.
-- This field will allow user to reorder their site awards.
--

ALTER TABLE `SiteAwards` ADD COLUMN `DisplayOrder` SMALLINT(6) NOT NULL DEFAULT '0' COMMENT 'Display order to show site awards in' AFTER `AwardDataExtra`;
