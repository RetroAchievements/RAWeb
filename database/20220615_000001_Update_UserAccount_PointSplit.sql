/* Joining against the Awarded table uses a lot of resources. 
 * Use a stored procedure to process one game at a time.
 */
DROP PROCEDURE IF EXISTS UpdateUserPoints;
DELIMITER #
CREATE PROCEDURE UpdateUserPoints()

BEGIN

  DECLARE n INT DEFAULT 0;
  DECLARE i INT DEFAULT 0;
  SELECT MAX(ID) FROM UserAccounts INTO n;
  SET i=1;

  WHILE i <= n DO
    UPDATE UserAccounts ua
    LEFT JOIN (
       SELECT ua2.ID AS UserID, 
            SUM(IF(aw.HardcoreMode LIKE '1', ach.Points, 0)) AS HardcorePoints,
            SUM(IF(aw.HardcoreMode LIKE '1', ach.TrueRatio, 0)) AS TruePoints,
            SUM(IF(aw.HardcoreMode LIKE '1', 0, ach.Points)) AS TotalPoints
       FROM Awarded AS aw
       LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
       LEFT JOIN UserAccounts ua2 ON aw.User = ua2.User
       WHERE ua2.ID = i AND ach.Flags = 3
        GROUP BY ua2.ID
    ) AS hc ON ua.ID=hc.UserID
    SET RAPoints = COALESCE(hc.HardcorePoints, 0),
    TrueRAPoints = COALESCE(hc.TruePoints, 0),
    RASoftcorePoints = COALESCE(hc.TotalPoints - hc.HardcorePoints, 0)
    WHERE ua.ID=i;
    SET i=i+1;
  END WHILE;

END #

DELIMITER ;
CALL UpdateUserPoints();
DROP PROCEDURE IF EXISTS UpdateUserPoints;