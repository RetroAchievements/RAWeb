<?php

declare(strict_types=1);

namespace App\Api\V2\GameHashes;

use App\Models\GameHash;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class GameHashSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = GameHash::class;

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
        return 'game-hashes';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('raMd5', 'md5')->readOnly(),
            Str::make('name')->readOnly(),
            Str::make('compatibility')->readOnly(),
            Str::make('patchUrl', 'patch_url')->readOnly(),

            DateTime::make('createdAt', 'created_at')->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),

            BelongsTo::make('game')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            WhereIn::make('compatibility', 'compatibility')->delimiter(','),
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
}
