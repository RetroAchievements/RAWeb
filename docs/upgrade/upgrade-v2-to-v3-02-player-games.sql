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
