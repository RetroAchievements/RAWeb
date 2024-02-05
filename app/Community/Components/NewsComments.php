<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Components\Grid;
use App\Models\News;
use App\Models\NewsComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NewsComments extends Grid
{
    public ?int $newsId = null;

    private ?News $news = null;

    protected array $pageSizes = [
        5,
        10,
    ];

    protected function resourceName(): string
    {
        return 'news.comment';
    }

    // public function mount(?int $take = null): void
    // {
    //     $this->take = $take;
    //     $this->updateQuery = !$take;
    // }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'news' => $this->news,
            ]
        );
    }

    /**
     * @return Builder<NewsComment>
     */
    protected function query(): Builder
    {
        /** @var News $news */
        $news = News::findOrFail($this->newsId);

        $this->news = $news;

        $query = $this->news->comments()->getQuery();

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->news);
        $this->authorize('viewAny', [NewsComment::class, $this->news]);
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        return $this->results;
    }
}
