<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\NewsCategory;
use App\Data\NewsData;
use App\Models\News;
use Illuminate\Support\Collection;

class BuildSiteReleaseNotesAction
{
    /**
     * Fetch the site release notes to display in the Latest Site Updates dialog.
     *
     * @return Collection<int, NewsData>
     */
    public function execute(int $limit = 5): Collection
    {
        $news = News::with('user')
            ->where('category', NewsCategory::SiteReleaseNotes)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $news->map(fn (News $news) => NewsData::fromNews($news));
    }
}
