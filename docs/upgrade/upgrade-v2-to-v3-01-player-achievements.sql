-- Source player_achievements from Awarded

INSERT IGNORE INTO player_achievements (user_id, achievement_id, unlocked_at, unlocked_hardcore_at)
SELECT
    u.ID,
    aw.AchievementID,
    aw.Date,
    awh.Date
FROM Awarded aw
    LEFT JOIN Awarded awh ON aw.AchievementID = awh.AchievementID
        AND aw.User = awh.User
        AND awh.HardcoreMode = 1
    LEFT JOIN UserAccounts u ON aw.User = u.User
    LEFT JOIN Achievements a ON aw.AchievementID = a.ID
WHERE
    aw.HardcoreMode = 0
    AND u.ID IS NOT NULL
    AND a.ID IS NOT NULL
ORDER BY
    aw.Date;
