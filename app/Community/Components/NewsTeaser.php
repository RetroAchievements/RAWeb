<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\News;
use App\Site\Components\Concerns\DeferLoading;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class NewsTeaser extends Component
{
    use DeferLoading;

    public function render(): View
    {
        return view('components.news.teaser')
            ->with('results', $this->loadDeferred());
    }

    /**
     * @return Collection<int, News>
     */
    protected function load(): Collection
    {
        return News::orderByDesc('created_at')->take(4)->with('user', 'media')->get();
    }
}
