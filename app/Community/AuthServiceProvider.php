<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Models\AchievementComment;
use App\Community\Models\AchievementSetClaim;
use App\Community\Models\Comment;
use App\Community\Models\Forum;
use App\Community\Models\ForumCategory;
use App\Community\Models\ForumTopic;
use App\Community\Models\ForumTopicComment;
use App\Community\Models\GameComment;
use App\Community\Models\MessageThread;
use App\Community\Models\News;
use App\Community\Models\NewsComment;
use App\Community\Models\TriggerTicket;
use App\Community\Models\UserActivity;
use App\Community\Models\UserComment;
use App\Community\Policies\AchievementCommentPolicy;
use App\Community\Policies\AchievementSetClaimPolicy;
use App\Community\Policies\CommentPolicy;
use App\Community\Policies\ForumCategoryPolicy;
use App\Community\Policies\ForumPolicy;
use App\Community\Policies\ForumTopicCommentPolicy;
use App\Community\Policies\ForumTopicPolicy;
use App\Community\Policies\GameCommentPolicy;
use App\Community\Policies\MessageThreadPolicy;
use App\Community\Policies\NewsCommentPolicy;
use App\Community\Policies\NewsPolicy;
use App\Community\Policies\TriggerTicketPolicy;
use App\Community\Policies\UserActivityPolicy;
use App\Community\Policies\UserCommentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AchievementComment::class => AchievementCommentPolicy::class,
        AchievementSetClaim::class => AchievementSetClaimPolicy::class,
        Comment::class => CommentPolicy::class,
        Forum::class => ForumPolicy::class,
        ForumCategory::class => ForumCategoryPolicy::class,
        ForumTopicComment::class => ForumTopicCommentPolicy::class,
        ForumTopic::class => ForumTopicPolicy::class,
        GameComment::class => GameCommentPolicy::class,
        MessageThread::class => MessageThreadPolicy::class,
        News::class => NewsPolicy::class,
        NewsComment::class => NewsCommentPolicy::class,
        TriggerTicket::class => TriggerTicketPolicy::class,
        UserActivity::class => UserActivityPolicy::class,
        UserComment::class => UserCommentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
