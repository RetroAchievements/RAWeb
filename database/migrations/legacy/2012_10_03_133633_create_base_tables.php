<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';
    protected Connection $dbConnection;

    public function up()
    {
        // NOTE: includes all migrations for the base tables until 2022_06_15

        /** @var Connection $dbConnection */
        $dbConnection = DB::connection($this->getConnection());
        $this->dbConnection = $dbConnection;

        // Migrate UserAccount bit types to boolean / tinyint(1)
        if (Schema::hasTable('UserAccounts')) {
            try {
                $this->dbConnection->getDoctrineColumn('UserAccounts', 'UserWallActive')->getType()->getName();
            } catch (Doctrine\DBAL\Exception) {
                // "Unknown database type bit requested, Doctrine\DBAL\Platforms\MySQL80Platform may not support it."

                // make sure the bit type is understood
                $this->dbConnection
                    ->getDoctrineConnection()
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping('bit', 'boolean');

                Schema::table('UserAccounts', function (Blueprint $table) {
                    $table->boolean('UserWallActive')->default(true)->change();
                    $table->boolean('Untracked')->default(false)->change();
                });
            }
        }

        if (!Schema::hasTable('Achievements')) {
            Schema::create('Achievements', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('GameID');
                $table->string('Title', 64);
                $table->string('Description');
                $table->text('MemAddr');
                $table->string('Progress')->nullable();
                $table->string('ProgressMax')->nullable();
                $table->string('ProgressFormat', 50)->nullable();
                $table->unsignedSmallInteger('Points')->default(0);
                $table->unsignedTinyInteger('Flags')->default(0);
                $table->string('Author', 32);
                $table->timestampTz('DateCreated')->nullable();
                $table->timestampTz('DateModified')->nullable()->useCurrent();
                $table->unsignedSmallInteger('VotesPos')->default(0);
                $table->unsignedSmallInteger('VotesNeg')->default(0);
                $table->string('BadgeName', 8)->default('00001');
                $table->unsignedSmallInteger('DisplayOrder')->default(0);
                $table->string('AssocVideo')->nullable();
                $table->unsignedInteger('TrueRatio')->default(0);

                $table->index('GameID');
                $table->index('Author');
                $table->index('Points');
                $table->index('TrueRatio');
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20191024_080000_Add_Achievement_Updated_Timestamp.sql
        // add a dedicated Updated timestamp for changes that are relevant for synchronisation accuracy
        if (!Schema::hasColumn('Achievements', 'Updated')) {
            Schema::table('Achievements', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Updated')->nullable();
                } else {
                    $table->timestampTz('Updated')->nullable()->useCurrent();
                }
            });
            // UPDATE `Achievements` SET `Updated` = `DateModified` WHERE DateModified IS NOT NULL;
        }

        if (!Schema::hasTable('Activity')) {
            Schema::create('Activity', function (Blueprint $table) {
                $table->increments('ID');
                $table->timestampTz('timestamp')->useCurrent();
                $table->timestampTz('lastupdate')->nullable();
                $table->unsignedSmallInteger('activitytype');
                $table->string('User', 32);
                $table->string('data', 20)->nullable();
                $table->string('data2', 12)->nullable();

                $table->index('User');
                $table->index('data');
                $table->index('activitytype');
                $table->index('timestamp');
                $table->index('lastupdate');
            });
        }

        if (!Schema::hasTable('ArticleTypeDimension')) {
            Schema::create('ArticleTypeDimension', function (Blueprint $table) {
                $table->unsignedTinyInteger('ArticleTypeID')->primary();
                $table->string('ArticleType', 50);
            });
        }

        if (!Schema::hasTable('Awarded')) {
            Schema::create('Awarded', function (Blueprint $table) {
                $table->string('User', 32);
                $table->unsignedInteger('AchievementID');
                $table->timestampTz('Date')->nullable()->useCurrent();
                $table->boolean('HardcoreMode')->default(0);

                $table->primary(['User', 'AchievementID', 'HardcoreMode']);
                $table->index('User');
                $table->index('AchievementID');
                $table->index('Date');
            });
        }

        Schema::dropIfExists('Chat');

        if (!Schema::hasTable('CodeNotes')) {
            Schema::create('CodeNotes', function (Blueprint $table) {
                $table->unsignedInteger('GameID');
                $table->unsignedInteger('Address')->comment('Decimal -> Hex');
                $table->unsignedInteger('AuthorID');
                $table->text('Note');

                $table->primary(['GameID', 'Address']);
                $table->index('GameID');
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('CodeNotes', ['Created', 'Updated'])) {
            Schema::table('CodeNotes', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `CodeNotes` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('Comment')) {
            Schema::create('Comment', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedTinyInteger('ArticleType');
                $table->unsignedInteger('ArticleID');
                $table->unsignedInteger('UserID');
                $table->text('Payload');
                $table->timestampTz('Submitted')->useCurrent();
                $table->timestampTz('Edited')->nullable();

                $table->index('ArticleID');
            });
        }

        if (!Schema::hasTable('Console')) {
            Schema::create('Console', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('Name', 50);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('Console', ['Created', 'Updated'])) {
            Schema::table('Console', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `Console` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasTable('DeletedModels')) {
            Schema::create('DeletedModels', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('ModelType', 30);
                $table->unsignedInteger('ModelID');
                $table->unsignedInteger('DeletedByUserID')->nullable();
                $table->timestampTz('Deleted')->nullable()->useCurrent();
            });
        }

        if (!Schema::hasTable('EmailConfirmations')) {
            Schema::create('EmailConfirmations', function (Blueprint $table) {
                $table->string('User', 32);
                $table->string('EmailCookie', 20)->index();
                $table->date('Expires');
            });
        }

        if (!Schema::hasTable('Forum')) {
            Schema::create('Forum', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('CategoryID')->index();
                $table->string('Title', 50);
                $table->string('Description');
                $table->unsignedInteger('LatestCommentID')->nullable();
                $table->unsignedInteger('DisplayOrder')->default(0);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('Forum', ['Created', 'Updated'])) {
            Schema::table('Forum', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `Forum` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('ForumCategory')) {
            Schema::create('ForumCategory', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('Name');
                $table->string('Description');
                $table->unsignedInteger('DisplayOrder')->default(0);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('ForumCategory', ['Created', 'Updated'])) {
            Schema::table('ForumCategory', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `ForumCategory` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('ForumTopic')) {
            Schema::create('ForumTopic', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('ForumID')->index();
                $table->string('Title');
                $table->string('Author', 32);
                $table->unsignedInteger('AuthorID');
                $table->timestampTz('DateCreated')->useCurrent();
                $table->unsignedInteger('LatestCommentID');
                $table->unsignedSmallInteger('RequiredPermissions')->default(0);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumn('ForumTopic', 'Updated')) {
            Schema::table('ForumTopic', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }

        if (!Schema::hasTable('ForumTopicComment')) {
            Schema::create('ForumTopicComment', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('ForumTopicID')->index();
                $table->text('Payload');
                $table->string('Author', 32);
                $table->unsignedInteger('AuthorID');
                $table->timestampTz('DateCreated')->index();
                $table->timestampTz('DateModified')->useCurrent()->useCurrentOnUpdate();
                $table->unsignedTinyInteger('Authorised')->nullable();
            });
        }

        if (!Schema::hasTable('Friends')) {
            Schema::create('Friends', function (Blueprint $table) {
                $table->string('User', 32)->index();
                $table->string('Friend', 32)->index();
                $table->tinyInteger('Friendship');
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('Friends', ['Created', 'Updated'])) {
            Schema::table('Friends', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `Friends` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('GameAlternatives')) {
            Schema::create('GameAlternatives', function (Blueprint $table) {
                $table->unsignedInteger('gameID')->index();
                $table->unsignedInteger('gameIDAlt')->index();
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('GameAlternatives', ['Created', 'Updated'])) {
            Schema::table('GameAlternatives', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `GameAlternatives` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('GameData')) {
            Schema::create('GameData', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('Title', 80);
                $table->unsignedTinyInteger('ConsoleID')->index();
                $table->unsignedInteger('ForumTopicID')->nullable();
                $table->unsignedInteger('Flags')->nullable();
                $table->string('ImageIcon', 50)->nullable()->default('/Images/000001.png');
                $table->string('ImageTitle', 50)->nullable()->default('/Images/000002.png');
                $table->string('ImageIngame', 50)->nullable()->default('/Images/000002.png');
                $table->string('ImageBoxArt', 50)->nullable()->default('/Images/000002.png');
                $table->string('Publisher', 50)->nullable();
                $table->string('Developer', 50)->nullable();
                $table->string('Genre', 50)->nullable();
                $table->string('Released', 50)->nullable();
                $table->unsignedTinyInteger('IsFinal')->default(0);
                $table->text('RichPresencePatch')->nullable();
                $table->unsignedInteger('TotalTruePoints')->default(0);

                $table->unique(['Title', 'ConsoleID']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('GameData', ['Created', 'Updated'])) {
            Schema::table('GameData', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `GameData` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('GameHashLibrary')) {
            Schema::create('GameHashLibrary', function (Blueprint $table) {
                $table->string('MD5', 32)->primary();
                $table->unsignedInteger('GameID');
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumn('GameHashLibrary', 'Created')) {
            Schema::table('GameHashLibrary', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                }
            });
            // UPDATE `GameHashLibrary` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20210320_020000_Add_GameHashLibrary_User.sql
        if (!Schema::hasColumn('GameHashLibrary', 'User')) {
            Schema::table('GameHashLibrary', function (Blueprint $table) {
                $table->string('User', 32)->nullable();
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20220308_000000_Add_GameHashLibrary_Details.sql
        if (!Schema::hasColumns('GameHashLibrary', ['Name', 'Labels'])) {
            Schema::table('GameHashLibrary', function (Blueprint $table) {
                $table->string('Name')->nullable();
                $table->string('Labels')->nullable();
            });
        }

        if (!Schema::hasTable('LeaderboardDef')) {
            Schema::create('LeaderboardDef', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('GameID')->index();
                $table->text('Mem');
                $table->string('Format', 50);
                $table->string('Title')->default('Leaderboard Title');
                $table->string('Description')->default('Leaderboard Description');
                $table->boolean('LowerIsBetter')->default(0);

                // https://github.com/RetroAchievements/RAWeb/blob/master/database/20211204_000000_Update_LeaderboardDef_DisplayOrder.sql
                $table->integer('DisplayOrder')->default(0);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('LeaderboardDef', ['Created', 'Updated'])) {
            Schema::table('LeaderboardDef', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `LeaderboardDef` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20210613_000000_Add_LeaderboardDef_Author.sql
        if (!Schema::hasColumn('LeaderboardDef', 'Author')) {
            Schema::table('LeaderboardDef', function (Blueprint $table) {
                $table->string('Author', 32)->nullable()->after('DisplayOrder');
            });
        }

        if (!Schema::hasTable('LeaderboardEntry')) {
            Schema::create('LeaderboardEntry', function (Blueprint $table) {
                $table->unsignedInteger('LeaderboardID')->index();
                $table->unsignedInteger('UserID');
                $table->integer('Score')->default(0);
                $table->dateTimeTz('DateSubmitted');

                $table->primary(['LeaderboardID', 'UserID']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumn('LeaderboardEntry', 'Created')) {
            Schema::table('LeaderboardEntry', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                }
            });
            // UPDATE `LeaderboardEntry` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        if (!Schema::hasTable('Messages')) {
            Schema::create('Messages', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('UserTo', 32)->index();
                $table->string('UserFrom', 32);
                $table->text('Title');
                $table->text('Payload');
                $table->timestampTz('TimeSent')->useCurrent();
                $table->boolean('Unread')->index();
                $table->unsignedInteger('Type')->comment('Not used');
            });
        }

        if (!Schema::hasTable('News')) {
            Schema::create('News', function (Blueprint $table) {
                $table->increments('ID');
                $table->timestampTz('Timestamp')->useCurrent();
                $table->string('Title')->nullable();
                $table->text('Payload');
                $table->string('Author', 32);
                $table->string('Link')->nullable();
                $table->string('Image')->nullable();
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumn('News', 'Updated')) {
            Schema::table('News', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }

        Schema::dropIfExists('PlaylistVideo');

        if (!Schema::hasTable('Rating')) {
            Schema::create('Rating', function (Blueprint $table) {
                $table->string('User', 32);
                $table->unsignedSmallInteger('RatingObjectType');
                $table->unsignedSmallInteger('RatingID');
                $table->unsignedSmallInteger('RatingValue');

                $table->primary(['User', 'RatingObjectType', 'RatingID']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('Rating', ['Created', 'Updated'])) {
            Schema::table('Rating', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `Rating` SET `Created` = NULL  WHERE `Created` IS NOT NULL;
        }

        Schema::dropIfExists('ScoreHistory');

        if (!Schema::hasTable('SetClaim')) {
            Schema::create('SetClaim', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('User', 32);
                $table->unsignedInteger('GameID');
                $table->unsignedInteger('ClaimType');
                $table->unsignedInteger('SetType');
                $table->unsignedInteger('Status');
                $table->unsignedInteger('Extension');
                $table->unsignedInteger('Special');
                $table->timestampTz('Created')->useCurrent();
                $table->timestampTz('Finished')->useCurrent();
                $table->timestampTz('Updated')->useCurrent();
            });
        }

        if (!Schema::hasTable('SetRequest')) {
            Schema::create('SetRequest', function (Blueprint $table) {
                $table->string('User', 32);
                $table->unsignedInteger('GameID');
                $table->timestampTz('Updated')->useCurrent();

                $table->primary(['User', 'GameID']);
            });
        }

        if (!Schema::hasTable('SiteAwards')) {
            Schema::create('SiteAwards', function (Blueprint $table) {
                $table->dateTimeTz('AwardDate');
                $table->string('User', 32)->index();
                $table->unsignedInteger('AwardType')->index();
                $table->unsignedInteger('AwardData')->nullable();
                $table->unsignedInteger('AwardDataExtra')->nullable();

                $table->unique(['User', 'AwardData', 'AwardType', 'AwardDataExtra']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190702_233400_Add_DisplayOrder_to_SiteAwards.sql
        if (!Schema::hasColumn('SiteAwards', 'DisplayOrder')) {
            Schema::table('SiteAwards', function (Blueprint $table) {
                $table->unsignedSmallInteger('DisplayOrder')->default(0)->after('AwardDataExtra')->comment('Display order to show site awards in');
            });
        }

        if (!Schema::hasTable('StaticData')) {
            Schema::create('StaticData', function (Blueprint $table) {
                $table->unsignedInteger('NumAchievements');
                $table->unsignedInteger('NumAwarded');
                $table->unsignedInteger('NumGames');
                $table->unsignedInteger('NumRegisteredUsers');
                $table->unsignedInteger('TotalPointsEarned');
                $table->unsignedInteger('LastAchievementEarnedID');
                $table->string('LastAchievementEarnedByUser', 32);
                $table->timestampTz('LastAchievementEarnedAt')->useCurrent()->useCurrentOnUpdate();
                $table->string('LastRegisteredUser', 32);
                $table->timestampTz('LastRegisteredUserAt')->nullable();
                $table->unsignedInteger('LastUpdatedGameID');
                $table->unsignedInteger('LastUpdatedAchievementID');
                $table->unsignedInteger('LastCreatedGameID');
                $table->unsignedInteger('LastCreatedAchievementID');
                $table->unsignedInteger('NextGameToScan')->default(1);
                $table->unsignedInteger('NextUserIDToScan')->default(1);
                $table->unsignedInteger('Event_AOTW_AchievementID')->default(1);
                $table->unsignedInteger('Event_AOTW_ForumID')->default(1);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20200111_000000_Add_Aotw_Start.sql
        if (!Schema::hasColumn('StaticData', 'Event_AOTW_StartAt')) {
            Schema::table('StaticData', function (Blueprint $table) {
                $table->timestampTz('Event_AOTW_StartAt')->nullable();
            });
        }

        if (!Schema::hasTable('Subscription')) {
            Schema::create('Subscription', function (Blueprint $table) {
                $table->enum('SubjectType', [
                    'ForumTopic',
                    'UserWall',
                    'GameTickets',
                    'GameWall',
                    'GameAchievements',
                    'Achievement',
                ]);
                $table->unsignedInteger('SubjectID');
                $table->unsignedInteger('UserID');
                $table->boolean('State');

                $table->primary(['SubjectType', 'SubjectID', 'UserID']);
            });
        }

        // NOTE: original migration missing
        if (!Schema::hasColumns('Subscription', ['Created', 'Updated'])) {
            Schema::table('Subscription', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent();
                }
            });
        }

        if (!Schema::hasTable('Ticket')) {
            Schema::create('Ticket', function (Blueprint $table) {
                $table->increments('ID');
                $table->unsignedInteger('AchievementID');
                $table->unsignedInteger('ReportedByUserID');
                $table->unsignedTinyInteger('ReportType');
                $table->text('ReportNotes');
                $table->timestampTz('ReportedAt')->nullable()->index();
                $table->timestampTz('ResolvedAt')->nullable();
                $table->unsignedInteger('ResolvedByUserID')->nullable();
                $table->unsignedTinyInteger('ReportState')->default(1);

                $table->unique(['AchievementID', 'ReportedByUserID']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumn('Ticket', 'Updated')) {
            Schema::table('Ticket', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20210806_000000_Add_Ticket_Hardcore.sql
        if (!Schema::hasColumn('Ticket', 'Hardcore')) {
            Schema::table('Ticket', function (Blueprint $table) {
                $table->boolean('Hardcore')->nullable()->after('ReportType');
            });
        }

        if (!Schema::hasTable('UserAccounts')) {
            Schema::create('UserAccounts', function (Blueprint $table) {
                $table->increments('ID');
                $table->string('User', 32)->unique();
                $table->string('SaltedPass', 32);
                $table->string('EmailAddress', 64);
                $table->tinyInteger('Permissions')->comment('-2=spam, -1=ban, 0=unconfirmed, 1=confirmed, 2=jr-dev, 3=dev, 4=admin');
                $table->unsignedInteger('RAPoints');
                $table->unsignedBigInteger('fbUser');
                $table->unsignedSmallInteger('fbPrefs')->nullable();
                $table->string('cookie', 100)->nullable();
                $table->string('appToken', 60)->nullable();
                $table->dateTimeTz('appTokenExpiry')->nullable();
                $table->unsignedSmallInteger('websitePrefs')->default(0);
                $table->timestampTz('LastLogin')->nullable();
                $table->unsignedInteger('LastActivityID')->nullable();
                $table->string('Motto', 50)->nullable();
                $table->unsignedInteger('ContribCount')->nullable()->comment('The Number of awarded achievements that this user was the author of');
                $table->unsignedInteger('ContribYield')->nullable()->comment('The total points allocated for achievements that this user has been the author of');
                $table->string('APIKey', 60)->nullable();
                $table->unsignedInteger('APIUses')->nullable();
                $table->unsignedInteger('LastGameID')->nullable();
                $table->string('RichPresenceMsg', 100)->nullable();
                $table->dateTimeTz('RichPresenceMsgDate')->nullable();
                $table->boolean('ManuallyVerified')->default(0)->comment('If 0, cannot post directly to forums without manual permission');
                $table->unsignedInteger('UnreadMessageCount')->nullable();
                $table->unsignedInteger('TrueRAPoints')->nullable();
                $table->boolean('UserWallActive')->default(1)->comment('Allow Posting to user wall');
                $table->string('PasswordResetToken', 32)->nullable();
                $table->boolean('Untracked');
                $table->string('email_backup')->nullable();

                $table->index(['User', 'Untracked']);
                $table->index(['TrueRAPoints', 'Untracked']);
                $table->index(['Untracked', 'RAPoints']);
                $table->index('LastActivityID');

                // https://github.com/RetroAchievements/RAWeb/blob/master/database/20200402_230000_Add_UserAccount_Index.sql
                $table->index(['RAPoints', 'Untracked']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('UserAccounts', ['Created', 'Updated'])) {
            Schema::table('UserAccounts', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent();
                }
            });
            // UPDATE `UserAccounts` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20200110_000000_Update_RP_size.sql
        // allow rp display to be up to 255 characters
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->string('RichPresenceMsg')->nullable()->change();
        });

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20200417_200000_Add_UserAccount_Password.sql
        if (!Schema::hasColumns('UserAccounts', ['Password'])) {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->string('Password')->nullable()->after('User');
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20201114_140000_Add_UserAccount_Deleted.sql
        if (!Schema::hasColumns('UserAccounts', ['DeleteRequested', 'Deleted'])) {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->timestampTz('DeleteRequested')->nullable();
                $table->timestampTz('Deleted')->nullable();
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20220615_000000_Add_UserAccount_SoftcorePoints.sql
        if (!Schema::hasColumns('UserAccounts', ['RASoftcorePoints'])) {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->unsignedInteger('RASoftcorePoints')->default(0)->after('RAPoints');

                // https://github.com/RetroAchievements/RAWeb/blob/master/database/20220810_000000_Add_UserAccount_SoftcorePoints_Key.sql
                $table->index(['RASoftcorePoints', 'Untracked']);
            });
        }

        if (!Schema::hasTable('Votes')) {
            Schema::create('Votes', function (Blueprint $table) {
                $table->string('User', 32);
                $table->unsignedInteger('AchievementID');
                $table->unsignedTinyInteger('Vote');

                $table->primary(['User', 'AchievementID']);
            });
        }

        // https://github.com/RetroAchievements/RAWeb/blob/master/database/20190918_080000_Add_Timestamps.sql
        if (!Schema::hasColumns('Votes', ['Created', 'Updated'])) {
            Schema::table('Votes', function (Blueprint $table) {
                if ($this->dbConnection->getDriverName() === 'sqlite') {
                    // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                    $table->timestampTz('Created')->nullable();
                    $table->timestampTz('Updated')->nullable()->useCurrentOnUpdate();
                } else {
                    $table->timestampTz('Created')->nullable()->useCurrent();
                    $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
            // UPDATE `Votes` SET `Created` = NULL WHERE `Created` IS NOT NULL;
        }
    }

    public function down()
    {
        // nope
    }
};
