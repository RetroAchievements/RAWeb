<?php

declare(strict_types=1);

namespace App\Filament\GlobalSearch;

use App\Filament\Resources\GameResource;
use App\Filament\Resources\UserResource;
use App\Support\Search\Actions\CalculateTitleRelevanceAction;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class RelevanceBasedGlobalSearchProvider implements GlobalSearchProvider
{
    public function __construct(
        protected readonly CalculateTitleRelevanceAction $calculateTitleRelevanceAction,
    ) {
    }

    public function getResults(string $query): ?GlobalSearchResults
    {
        /** @var array<class-string<resource>> $resources */
        $resources = Filament::getResources();

        /** @var array<array{label: string, results: Collection<int, GlobalSearchResult>, avgRelevance: float}> $categorizedResults */
        $categorizedResults = [];
        foreach ($resources as $resource) {
            if (!$resource::canGloballySearch()) {
                continue;
            }

            /** @var Collection<int, GlobalSearchResult> $results */
            $results = $resource::getGlobalSearchResults($query);
            if ($results->isEmpty()) {
                continue;
            }

            $categorizedResults[] = [
                'label' => $resource::getPluralModelLabel(),
                'results' => $results,
                'avgRelevance' => $this->calculateCategoryRelevance($query, $results, $resource),
            ];
        }

        // Sort categories by average relevance (highest first).
        usort($categorizedResults, fn (array $a, array $b): int => $b['avgRelevance'] <=> $a['avgRelevance']);

        $builder = GlobalSearchResults::make();
        foreach ($categorizedResults as $category) {
            $builder->category($category['label'], $category['results']);
        }

        return $builder;
    }

    /**
     * Calculate average relevance for a category of search results.
     * Applies category-specific boosts based on query patterns.
     *
     * @param Collection<int, GlobalSearchResult> $results
     */
    private function calculateCategoryRelevance(string $query, Collection $results, string $resource): float
    {
        if ($results->isEmpty()) {
            return 0.0;
        }

        $avgRelevance = $results
            ->map(function ($result) use ($query) {
                $title = $result->title instanceof Htmlable
                    ? $result->title->toHtml()
                    : $result->title;

                return $this->calculateTitleRelevanceAction->execute($query, $title);
            })
            ->avg() ?? 0.0;

        $isMultiWordQuery = str_word_count($query) > 1;

        // Games: boost for multi-word queries since game titles are typically multi-word.
        if ($resource === GameResource::class && $isMultiWordQuery && $avgRelevance > 0.5) {
            return min(1.0, $avgRelevance * 1.2);
        }

        // Users: penalize for multi-word queries since usernames are always single words.
        if ($resource === UserResource::class && $isMultiWordQuery && $avgRelevance < 1.0) {
            return $avgRelevance * 0.7;
        }

        return $avgRelevance;
    }
}
