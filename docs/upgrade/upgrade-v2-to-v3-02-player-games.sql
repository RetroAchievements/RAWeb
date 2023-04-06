-- Source player_games from Activity

INSERT IGNORE INTO player_games (user_id, game_id, created_at)
SELECT
    u.ID AS user_id,
    act.data AS game_id,
    MIN(act.timestamp) created_at
FROM Activity act
     LEFT JOIN UserAccounts u ON act.User = u.User
     LEFT JOIN GameData g ON act.data = g.ID
WHERE
    act.activitytype = 3
    AND u.ID IS NOT NULL
    AND g.ID IS NOT NULL
GROUP BY
    user_id,
    game_id;

-- Source player_games from player_achievements

INSERT IGNORE INTO player_games (user_id, game_id, first_unlock_at, created_at)
SELECT
    pa.user_id,
    g.ID as game_id,
    MIN(pa.unlocked_at) as first_unlock_at,
    MIN(pa.unlocked_at) as created_at
FROM player_achievements pa
    LEFT JOIN Achievements a ON a.ID = pa.achievement_id
    LEFT JOIN GameData g ON g.ID = a.GameID
GROUP BY
    pa.user_id,
    g.ID;
