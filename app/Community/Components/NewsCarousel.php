<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NewsCarousel extends Component
{
    public function render(): View
    {
        $results = News::orderByDesc('created_at')->take(7)->with('user', 'media')->get();

        return view('components.news.carousel')
            ->with('results', $results);
    }
}
