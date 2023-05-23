# Upgrade from v2.x to v3.0

1. Upgrade setup

```shell
composer setup
```

2. Upgrade schema

Clean up invalid data before running migrations:

```sql
DELETE FROM Achievements WHERE GameID = 0;
DELETE FROM LeaderboardEntry WHERE LeaderboardID = 0;
UPDATE GameData SET ConsoleID = 101 WHERE ConsoleID = 99;
```

```shell
php artisan migrate
```

This may take some time for tables with a lot of data.

> **Note**
> Running `composer mfs` (alias for `php artisan migrate:refresh --seed`) will remove all columns
> and data that might have been migrate from then on.
> The V1 base tables however remain protected and will not be dropped/truncated.
> It's advised to not use `composer mfs` if you don't want to lose any data.
 
To roll back the migration (e.g. to switch back to a pre-v3 branch):

```shell
php artisan migrate:rollback
```

3. Migrate data

Run the following queries and sync commands below to populate the new columns and tables:

> **Note**
> Depending on the Awarded table size those queries can take several hours.
> If you had production dumps imported prior you should use the dumps to speed up the process.
> Note that those only include data up to 2023-02-01 00:00:00. 
> Any remaining entries have to be synced manually - see sync commands below.

- [01-player-achievements.sql](upgrade-v2-to-v3-01-player-achievements.sql)
  - Import https://files.retroachievements.org/db/ra-web-player_achievements.gz

- [02-player-games.sql](upgrade-v2-to-v3-02-player-games.sql)
  - Import https://files.retroachievements.org/db/ra-web-player_games.gz

- [03-leaderboard-entries.sql](upgrade-v2-to-v3-03-leaderboard-entries.sql)
  - Import https://files.retroachievements.org/db/ra-web-leaderboard_entries.gz

```shell
php artisan ra:sync:status
```

If you imported or prepared any data prior you can update the sync_status to save time. 

```sql
UPDATE sync_status SET reference = '2023-02-01 00:00:00' WHERE kind = 'leaderboard_entries';
UPDATE sync_status SET reference = '2023-02-01 00:00:00' WHERE kind = 'player_achievements';
UPDATE sync_status SET reference = '2023-02-01 00:00:00' WHERE kind = 'player_games';
```

Run sync commands:

```shell
php artisan ra:sync:leaderboard-entries
php artisan ra:sync:player-achievements
php artisan ra:sync:player-games
```
