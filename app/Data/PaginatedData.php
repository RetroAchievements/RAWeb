<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

// The TypeScript transformer is not capable of recognizing that this resource
// should accept a generic. We'll fix the type with an override in the front-end.
// We'll give the output type an "__UNSAFE" prefix as a warning that it shouldn't
// be directly used by developers.

/**
 * @template T
 */
#[TypeScript('__UNSAFE_PaginatedData')]
class PaginatedData extends Data
{
    public function __construct(
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
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
     * @param LengthAwarePaginator<T> $paginator
     * @return self<T>
     */
    public static function fromLengthAwarePaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
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
