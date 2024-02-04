<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\Forum;
use App\Community\Models\ForumTopic;
use App\Components\Grid;
use Illuminate\Database\Eloquent\Builder;

class ForumTopics extends Grid
{
    public string $display = 'list';

    public bool $defer = false;

    public ?int $forumId = null;

    private ?Forum $forum = null;

    protected array $pageSizes = [
        15,
    ];

    protected function resourceName(): string
    {
        return 'forum-topic';
    }

    public function mount(int $forumId): void
    {
        $this->forumId = $forumId;
    }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'forum' => $this->forum,
            ]
        );
    }

    /**
     * @return Builder<ForumTopic>
     */
    protected function query(): Builder
    {
        /** @var Forum $forum */
        $forum = Forum::findOrFail($this->forumId);

        $this->forum = $forum;

        $query = $this->forum->topics()
            ->withCount('comments')
            ->with('latestComment')
            ->orderbyLatestActivity('desc')
            ->getQuery();

        // TODO: filter comments (show/hide unauthorised posts)
        // if (!$nextCommentAuthorised && !$showAuthoriseActions) {
        //     continue; // Ignore this comment for the rest
        // }

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->forum);
        $this->authorize('viewAny', [ForumTopic::class, $this->forum]);
    }
}
