UPDATE GameData gd
LEFT JOIN (
  SELECT GameID, count(*) as AchievementCount, sum(Points) as TotalPoints
  FROM Achievements
  WHERE Flags=3 
  GROUP BY GameID
) AS ach ON ach.GameID=gd.ID
LEFT JOIN (
  SELECT GameID, count(*) as LeaderboardCount
  FROM LeaderboardDef
  WHERE DisplayOrder>=0
  GROUP BY GameID
) AS lb ON lb.GameID=gd.ID
SET gd.CoreAchievementCount=IFNULL(ach.AchievementCount, 0),
    gd.LeaderboardCount=IFNULL(lb.LeaderboardCount, 0),
    gd.Points=IFNULL(ach.TotalPoints, 0),
    gd.MetricsUpdated=now();

/* Joining against the Awarded table uses a lot of resources. 
 * Use a stored procedure to process one game at a time.
 */
DROP PROCEDURE IF EXISTS LoadGameMetrics;
DELIMITER #
CREATE PROCEDURE LoadGameMetrics()
BEGIN

  DECLARE n INT DEFAULT 0;
  DECLARE i INT DEFAULT 0;
  SELECT MAX(ID) FROM GameData INTO n;
  SET i=1;

  WHILE i <= n DO
    UPDATE GameData gd
    LEFT JOIN (
      SELECT ach.GameID, count(DISTINCT aw.User) as PlayerCount
      FROM Awarded aw
      INNER JOIN Achievements ach ON aw.AchievementID=ach.ID
      INNER JOIN UserAccounts ua ON aw.User=ua.User
      WHERE ach.GameID=i AND ach.Flags=3 AND !ua.Untracked
      GROUP BY ach.GameID
    ) as p ON p.GameID=gd.ID
    SET gd.PlayerCount=IFNULL(p.PlayerCount, 0),
        gd.MetricsUpdated=now()
    WHERE gd.ID=i;

    SET i=i+1;
  END WHILE;

END #

DELIMITER ;
CALL LoadGameMetrics();
DROP PROCEDURE IF EXISTS LoadGameMetrics;
