<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript('PaginatedData<TItems>')]
class PaginatedData extends Data
{
    public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        #[LiteralTypeScriptType('TItems[]')]
        public array $items,

        #[TypeScriptType([
            'firstPageUrl' => 'string | null',
            'lastPageUrl' => 'string | null',
            'previousPageUrl' => 'string | null',
            'nextPageUrl' => 'string | null',
        ])]
        public array $links,
    ) {
    }

    /**
     * @template TItems
     * @param LengthAwarePaginator<TItems> $paginator
     */
    public static function fromLengthAwarePaginator(LengthAwarePaginator $paginator, ?int $total = null): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $total ?? $paginator->total(),
            items: $paginator->items(),
            links: [
                'firstPageUrl' => $paginator->url(1),
                'lastPageUrl' => $paginator->url($paginator->lastPage()),
                'previousPageUrl' => $paginator->previousPageUrl(),
                'nextPageUrl' => $paginator->nextPageUrl(),
            ]
        );
    }
}
