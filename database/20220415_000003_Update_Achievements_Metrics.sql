/* Joining against the Awarded table uses a lot of resources.
 * Use a stored procedure to process one achievement at a time.
 */
DROP PROCEDURE IF EXISTS LoadAchievementMetrics;
DELIMITER #
CREATE PROCEDURE LoadAchievementMetrics()
BEGIN

  DECLARE n INT DEFAULT 0;
  DECLARE i INT DEFAULT 0;
  SELECT MAX(ID) FROM Achievements INTO n;
  SET i=1;

  WHILE i <= n DO
    UPDATE Achievements ach
    LEFT JOIN (
      SELECT aw.AchievementID,
        (SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwardedHardcore,
        (COUNT(aw.AchievementID) - SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwarded
      FROM Awarded aw
      INNER JOIN UserAccounts ua ON aw.User=ua.User
      WHERE aw.AchievementID=i AND !ua.Untracked
    ) as u ON u.AchievementID=ach.ID
    SET ach.UnlockCount=IFNULL(u.NumAwarded, 0),
        ach.HardcoreUnlockCount=IFNULL(u.NumAwardedHardcore, 0),
        ach.MetricsUpdated=now()
    WHERE ach.ID=i;

    SET i=i+1;
  END WHILE;

END #

DELIMITER ;
CALL LoadAchievementMetrics();
DROP PROCEDURE IF EXISTS LoadAchievementMetrics;
