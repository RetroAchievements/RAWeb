<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Models\News;
use App\Models\User;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Support\Concerns\HandlesResources;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Spatie\QueryBuilder\QueryBuilder;

class Supersearch extends Component
{
    use AuthorizesRequests;
    use HandlesResources;

    public bool $autoFocus = false;
    public bool $updateQuery = false;
    public bool $dropdown = false;
    public ?string $inputId = null;
    public ?string $search = null;
    private array $results = [];
    private int $total = 0;

    public function mount(): void
    {
        if ($this->updateQuery) {
            $this->search = request('search');
        }
    }

    public function getQueryString(): array
    {
        return $this->updateQuery ? ['search'] : [];
    }

    public function render(): View
    {
        return view('components.search.supersearch')
            ->with('results', $this->search());
    }

    private function search(): array
    {
        $this->total = 0;
        $this->results = [];

        if (!empty($this->search) && mb_strlen($this->search) >= 3) {
            $this->performSearches($this->searchables());
        }

        return $this->results;
    }

    private function searchables(): array
    {
        return [
            Achievement::class => [
                'limit' => 5,
            ],
            Game::class => [
                'limit' => 5,
            ],
            System::class => [
                'limit' => 5,
            ],
            User::class => [
                'limit' => 5,
            ],
            News::class => [
                'limit' => 3,
            ],
        ];
    }

    private function performSearches(array $searchables): void
    {
        foreach ($searchables as $searchable => $options) {
            $resourceType = resource_type($searchable);
            if (!$resourceType) {
                continue;
            }
            $resourceResults = $this->searchResource($resourceType, (string) $searchable, $options);
            if ($resourceResults && $resourceResults->count()) {
                $this->results[$resourceType] = $resourceResults;
                $this->total += $resourceResults->count();
            }
        }
    }

    /**
     * @return Collection<int, Model>|null
     */
    private function searchResource(string $resourceName, string $resourceClass, array $options = []): Collection|null
    {
        try {
            $query = $this->resourceQuery($resourceName);

            $this->authorize('viewAny', $resourceClass);

            $searched = $this->resourceClass($resourceName)::search($this->search);
            $query->whereIn('id', $searched->get()->pluck('id')->toArray());

            return QueryBuilder::for($query)
                ->take($options['limit'] ?? 3)->get();
        } catch (Exception) {
            // not allowed
        }

        return null;
    }
}
