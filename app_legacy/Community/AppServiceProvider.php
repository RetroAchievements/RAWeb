<?php

declare(strict_types=1);

namespace LegacyApp\Community;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Community\Models\Comment;
use LegacyApp\Community\Models\Forum;
use LegacyApp\Community\Models\ForumCategory;
use LegacyApp\Community\Models\ForumTopic;
use LegacyApp\Community\Models\ForumTopicComment;
use LegacyApp\Community\Models\Message;
use LegacyApp\Community\Models\News;
use LegacyApp\Community\Models\Rating;
use LegacyApp\Community\Models\Ticket;
use LegacyApp\Community\Models\UserActivity;
use LegacyApp\Community\Models\UserGameListEntry;
use LegacyApp\Community\Models\UserRelation;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);
        }

        Relation::morphMap([
            'forum' => Forum::class,
            'forum-category' => ForumCategory::class,
            'forum-topic' => ForumTopic::class,
            'forum-topic-comment' => ForumTopicComment::class,

            'comment' => Comment::class,
            'message' => Message::class,
            'news' => News::class,
            'rating' => Rating::class,
            'user-activity' => UserActivity::class,
            'user-game-list-entry' => UserGameListEntry::class,
            'user-relation' => UserRelation::class,

            'achievement-set-claim' => AchievementSetClaim::class,
            // 'achievement.comment' => AchievementComment::class,
            // 'game.comment' => GameComment::class,
            // 'news.comment' => NewsComment::class,
            // 'user.comment' => UserComment::class,
            'ticket' => Ticket::class,
        ]);
    }
}
