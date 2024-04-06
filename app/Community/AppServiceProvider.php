<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Commands\SyncComments;
use App\Community\Commands\SyncForumCategories;
use App\Community\Commands\SyncForums;
use App\Community\Commands\SyncForumTopics;
use App\Community\Commands\SyncNews;
use App\Community\Commands\SyncRatings;
use App\Community\Commands\SyncTickets;
use App\Community\Commands\SyncUserRelations;
use App\Community\Commands\SyncVotes;
use App\Community\Components\ActivePlayers;
use App\Community\Components\DeveloperGameStatsTable;
use App\Community\Components\ForumRecentPosts;
use App\Community\Components\GlobalStatistics;
use App\Community\Components\MessageIcon;
use App\Community\Components\UserCard;
use App\Community\Components\UserProfileMeta;
use App\Community\Components\UserProgressionStatus;
use App\Community\Components\UserRecentlyPlayed;
use App\Models\AchievementComment;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\GameComment;
use App\Models\Message;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\Rating;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TriggerTicket;
use App\Models\TriggerTicketComment;
use App\Models\UserActivity;
use App\Models\UserComment;
use App\Models\UserGameListEntry;
use App\Models\UserRelation;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
            'user-activity' => UserActivity::class,
            'user-game-list-entry' => UserGameListEntry::class,
            'user-relation' => UserRelation::class,
            'vote' => Vote::class,
        ]);

        AchievementComment::disableSearchSyncing();
        Comment::disableSearchSyncing();
        Forum::disableSearchSyncing();
        ForumCategory::disableSearchSyncing();
        ForumTopic::disableSearchSyncing();
        ForumTopicComment::disableSearchSyncing();
        GameComment::disableSearchSyncing();
        Message::disableSearchSyncing();
        News::disableSearchSyncing();
        NewsComment::disableSearchSyncing();
        Ticket::disableSearchSyncing();
        TriggerTicketComment::disableSearchSyncing();
        UserComment::disableSearchSyncing();

        Blade::component('active-players', ActivePlayers::class);
        Blade::component('developer-game-stats-table', DeveloperGameStatsTable::class);
        Blade::component('global-statistics', GlobalStatistics::class);
        Blade::component('forum-recent-posts', ForumRecentPosts::class);
        Blade::component('user-card', UserCard::class);
        Blade::component('user-profile-meta', UserProfileMeta::class);
        Blade::component('user-progression-status', UserProgressionStatus::class);
        Blade::component('user-recently-played', UserRecentlyPlayed::class);

        // Livewire::component('forum-topics', ForumTopics::class);
        //
        // Livewire::component('achievement.comments', AchievementComments::class);
        // Livewire::component('forum-topic-comments', ForumTopicComments::class);
        // Livewire::component('game.comments', GameComments::class);
        // Livewire::component('news.comments', NewsComments::class);
        // Livewire::component('user.comments', UserComments::class);

        Livewire::component('message-icon', MessageIcon::class);
        // Blade::component('news-carousel', NewsCarousel::class);
        // Livewire::component('news-grid', NewsGrid::class);
        // Livewire::component('news-teaser', NewsTeaser::class);
        // Livewire::component('user-activity-feed', UserActivityFeed::class);
    }
}
