<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Commands\SyncComments;
use App\Community\Commands\SyncForumCategories;
use App\Community\Commands\SyncForums;
use App\Community\Commands\SyncForumTopics;
use App\Community\Commands\SyncMessages;
use App\Community\Commands\SyncNews;
use App\Community\Commands\SyncRatings;
use App\Community\Commands\SyncTickets;
use App\Community\Commands\SyncUserRelations;
use App\Community\Commands\SyncVotes;
use App\Community\Models\AchievementComment;
use App\Community\Models\AchievementSetClaim;
use App\Community\Models\Comment;
use App\Community\Models\Forum;
use App\Community\Models\ForumCategory;
use App\Community\Models\ForumTopic;
use App\Community\Models\ForumTopicComment;
use App\Community\Models\GameComment;
use App\Community\Models\Message;
use App\Community\Models\News;
use App\Community\Models\NewsComment;
use App\Community\Models\Rating;
use App\Community\Models\Subscription;
use App\Community\Models\Ticket;
use App\Community\Models\TriggerTicket;
use App\Community\Models\TriggerTicketComment;
use App\Community\Models\UserActivityLegacy;
use App\Community\Models\UserComment;
use App\Community\Models\UserGameListEntry;
use App\Community\Models\UserRelation;
use App\Community\Models\Vote;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncComments::class,
                SyncForumCategories::class,
                SyncForums::class,
                SyncForumTopics::class,
                SyncMessages::class,
                SyncNews::class,
                SyncRatings::class,
                SyncTickets::class,
                SyncUserRelations::class,
                SyncVotes::class,
            ]);
        }

        $this->loadMigrationsFrom([database_path('migrations/community')]);

        Relation::morphMap([
            'achievement.comment' => AchievementComment::class,
            'achievement-set-claim' => AchievementSetClaim::class,
            'comment' => Comment::class,
            'forum' => Forum::class,
            'forum-category' => ForumCategory::class,
            'forum-topic' => ForumTopic::class,
            'forum-topic-comment' => ForumTopicComment::class,
            'game.comment' => GameComment::class,
            'message' => Message::class,
            'news' => News::class,
            'news.comment' => NewsComment::class,
            'rating' => Rating::class,
            'subscription' => Subscription::class,
            'ticket' => Ticket::class,
            'trigger.ticket' => TriggerTicket::class,
            'trigger.ticket.comment' => TriggerTicketComment::class,
            'user.comment' => UserComment::class,
            'user-activity' => UserActivityLegacy::class,
            // TODO 'user-activity' => UserActivity::class,
            'user-game-list-entry' => UserGameListEntry::class,
            'user-relation' => UserRelation::class,
            'vote' => Vote::class,
        ]);

        // Livewire::component('forum-topics', ForumTopics::class);
        //
        // Livewire::component('achievement.comments', AchievementComments::class);
        // Livewire::component('forum-topic-comments', ForumTopicComments::class);
        // Livewire::component('game.comments', GameComments::class);
        // Livewire::component('news.comments', NewsComments::class);
        // Livewire::component('user.comments', UserComments::class);
        //
        // Livewire::component('message-icon', MessageIcon::class);
        // Blade::component('news-carousel', NewsCarousel::class);
        // Livewire::component('news-grid', NewsGrid::class);
        // Livewire::component('news-teaser', NewsTeaser::class);
        // Livewire::component('user-activity-feed', UserActivityFeed::class);
    }
}
