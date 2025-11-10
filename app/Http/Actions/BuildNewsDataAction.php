<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\NewsCategory;
use App\Data\NewsData;
use App\Models\News;
use Illuminate\Support\Collection;

class BuildNewsDataAction
{
    /**
     * Fetch the news items to show on the home page.
     * Pinned news items should always appear first.
     *
     * @return Collection<int, NewsData>
     */
    public function execute(int $limit = 9): Collection
    {
        $news = News::with('user')
            ->where(function ($query) {
                $query->where('category', '!=', NewsCategory::SiteReleaseNotes)
                    ->orWhereNull('category');
            })
            ->orderByDesc('pinned_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $news->map(fn (News $news) => NewsData::fromNews($news));
    }
}
