# Upgrade from v3.x to v3.3

1. Update set requests

Manually remove duplicates from SetRequest table:
```sql
SELECT sr.User, sr.GameID, COUNT(*) count FROM SetRequest sr GROUP BY sr.User, sr.GameID HAVING count > 1;
```
Populate user_id column:
```sql
UPDATE SetRequest sr SET user_id = (SELECT ID FROM UserAccounts ua WHERE ua.`User` = sr.`User`);
```
Populate type column:
```sql
UPDATE SetRequest sr SET `type` = 'achievement_set_request';
```
