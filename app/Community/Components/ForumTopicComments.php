<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Components\Grid;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ForumTopicComments extends Grid
{
    public ?int $topicId = null;

    private ?ForumTopic $topic = null;

    protected array $pageSizes = [
        15,
    ];

    protected function resourceName(): string
    {
        return 'forum-topic-comment';
    }

    public function mount(int $topicId, ?int $take = null): void
    {
        $this->topicId = $topicId;
        $this->take = $take;
        $this->updateQuery = !$take;
    }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'topic' => $this->topic,
            ]
        );
    }

    /**
     * @return Builder<ForumTopicComment>
     */
    protected function query(): Builder
    {
        /** @var ForumTopic $topic */
        $topic = ForumTopic::findOrFail($this->topicId);

        $this->topic = $topic;

        $query = $this->topic->comments()
            /*
             * deleted comments on forum topics will be displayed as either deleted or blanked out
             * to preserve pagination index
             */
            // TODO ->withTrashed()
            /*
             * always order by creation date to preserve page index
             */
            ->orderBy('created_at')
            ->getQuery();

        // TODO: filter comments (show/hide unauthorised posts)
        // if (!$nextCommentAuthorised && !$showAuthoriseActions) {
        //     continue; // Ignore this comment for the rest
        // }

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->topic);
        $this->authorize('viewAny', [ForumTopicComment::class, $this->topic]);
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        return $this->results;
    }
}
