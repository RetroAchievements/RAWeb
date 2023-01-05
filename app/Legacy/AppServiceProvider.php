<?php

declare(strict_types=1);

namespace App\Legacy;

use App\Legacy\Commands\RecalculateContributionYield;
use App\Legacy\Models\Achievement;
use App\Legacy\Models\AchievementSetClaim;
use App\Legacy\Models\AchievementSetRequest;
use App\Legacy\Models\Comment;
use App\Legacy\Models\Forum;
use App\Legacy\Models\ForumCategory;
use App\Legacy\Models\ForumTopic;
use App\Legacy\Models\ForumTopicComment;
use App\Legacy\Models\Game;
use App\Legacy\Models\GameHash;
use App\Legacy\Models\Leaderboard;
use App\Legacy\Models\LeaderboardEntry;
use App\Legacy\Models\MemoryNote;
use App\Legacy\Models\Message;
use App\Legacy\Models\News;
use App\Legacy\Models\PlayerAchievement;
use App\Legacy\Models\PlayerBadge;
use App\Legacy\Models\Rating;
use App\Legacy\Models\System;
use App\Legacy\Models\Ticket;
use App\Legacy\Models\User;
use App\Legacy\Models\UserRelation;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                /*
                 * Community (Includes developer features that are not part of the core platform features)
                 */
                RecalculateContributionYield::class,
            ]);
        }

        $this->loadMigrationsFrom([database_path('migrations/legacy')]);

        Model::shouldBeStrict(!$this->app->isProduction());

        Relation::morphMap([

            // Site

            'user' => User::class,

            // Platform

            'achievement' => Achievement::class,
            'achievement-set-claim' => AchievementSetClaim::class,
            'achievement-set-request' => AchievementSetRequest::class,
            'game' => Game::class,
            'leaderboard' => Leaderboard::class,
            'leaderboard-entry' => LeaderboardEntry::class,
            'memory-note' => MemoryNote::class,
            'game-hash' => GameHash::class,
            'system' => System::class,
            'player-achievement' => PlayerAchievement::class,
            'player-badge' => PlayerBadge::class,

            // Community

            'forum' => Forum::class,
            'forum-category' => ForumCategory::class,
            'forum-topic' => ForumTopic::class,
            'forum-topic-comment' => ForumTopicComment::class,
            'comment' => Comment::class,
            // 'achievement.comment' => AchievementComment::class,
            // 'game.comment' => GameComment::class,
            // 'news.comment' => NewsComment::class,
            // 'user.comment' => UserComment::class,
            'ticket' => Ticket::class,
            'user-relation' => UserRelation::class,
            'message' => Message::class,
            'news' => News::class,
            'rating' => Rating::class,
        ]);

        // TODO remove
        $this->app->singleton('mysqli', function () {
            try {
                $db = mysqli_connect(
                    config('database.connections.mysql_legacy.host'),
                    config('database.connections.mysql_legacy.username'),
                    config('database.connections.mysql_legacy.password'),
                    config('database.connections.mysql_legacy.database'),
                    (int) config('database.connections.mysql_legacy.port')
                );
                if (!$db) {
                    throw new Exception('Could not connect to database. Please try again later.');
                }
                mysqli_set_charset($db, 'latin1');
                mysqli_query($db, "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

                return $db;
            } catch (Exception $exception) {
                if (app()->environment('local', 'testing')) {
                    throw $exception;
                } else {
                    echo 'Could not connect to database. Please try again later.';
                    exit;
                }
            }
        });
    }
}
