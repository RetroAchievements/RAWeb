/* Update the # of games count to only include games with achievements */
UPDATE StaticData AS sd
SET sd.NumGames = (SELECT COUNT(DISTINCT ach.GameID)
                   FROM GameData gd
                   INNER JOIN Achievements ach ON ach.GameID = gd.ID);