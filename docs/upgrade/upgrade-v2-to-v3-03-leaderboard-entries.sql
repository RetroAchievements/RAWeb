-- Migrate leaderboard_entries from LeaderboardEntry

UPDATE LeaderboardEntry SET Created = DateSubmitted WHERE Created IS NULL;

INSERT IGNORE INTO leaderboard_entries (leaderboard_id, user_id, score, created_at, updated_at)
SELECT
    le.LeaderboardID,
    le.UserID,
    le.Score,
    le.Created,
    le.DateSubmitted
FROM LeaderboardEntry le
    LEFT JOIN LeaderboardDef l ON le.LeaderboardID = l.ID
    LEFT JOIN UserAccounts u ON le.UserID = u.ID
WHERE
    u.ID IS NOT NULL
    AND l.ID IS NOT NULL
ORDER BY le.Created;
