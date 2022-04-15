ALTER TABLE Achievements ADD COLUMN UnlockCount int(11) NOT NULL DEFAULT 0;
ALTER TABLE Achievements ADD COLUMN HardcoreUnlockCount int(11) NOT NULL DEFAULT 0;
ALTER TABLE Achievements ADD COLUMN MetricsUpdated timestamp NULL;
