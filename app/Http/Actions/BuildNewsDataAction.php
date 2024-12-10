<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Data\NewsData;
use App\Models\News;
use Illuminate\Support\Collection;

class BuildNewsDataAction
{
    /**
     * @return Collection<int, NewsData>
     */
    public function execute(int $limit = 9): Collection
    {
        $news = News::orderByDesc('Timestamp')
            ->with('user')
            ->limit($limit)
            ->get();

        return $news->map(fn (News $news) => NewsData::fromNews($news));
    }
}
