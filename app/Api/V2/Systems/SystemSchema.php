<?php

namespace App\Api\V2\Systems;

use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class SystemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = System::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'systems';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('nameFull', 'name_full')->sortable(),
            Str::make('nameShort', 'name_short')->sortable(),
            Str::make('manufacturer'),
            Str::make('iconUrl')->readOnly(),
            Boolean::make('active'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('active'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(50);
    }

    /**
     * Build an index query for this resource.
     * Excludes non-game systems (Hubs, Events).
     *
     * @param Builder<System> $query
     * @return Builder<System>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        return $query->where('ID', '!=', System::Hubs)
            ->where('ID', '!=', System::Events);
    }
}
