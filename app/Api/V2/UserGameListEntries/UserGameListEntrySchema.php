<?php

declare(strict_types=1);

namespace App\Api\V2\UserGameListEntries;

use App\Community\Enums\UserGameListType;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Policies\UserGameListEntryPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserGameListEntrySchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = UserGameListEntry::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows most recently added entries first.
     */
    protected $defaultSort = '-createdAt';

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'game.system',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'user-game-list-entries';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // The model column is `type`, but exposing `type` as a JSON:API
            // attribute is confusing -- `type` is also the JSON:API document
            // field for the resource type identifier. Rename to `kind`.
            Str::make('kind', 'type')->sortable()->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),

            Number::make('gameId', 'game_id')->readOnly(),
            Str::make('gameTitle')->readOnly(),
            Str::make('gameIconUrl')->readOnly(),
            Number::make('systemId')->readOnly(),
            Str::make('systemName')->readOnly(),
            Number::make('pointsTotal')->readOnly(),
            Number::make('achievementsPublished')->readOnly(),

            BelongsTo::make('game')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            Where::make('gameId', 'game_id'),
            new UserGameListEntryKindFilter(),
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
     * When accessed as a User relationship, restrict visibility per-kind by
     * dispatching to UserGameListEntryPolicy::view for every UserGameListType
     * case. The policy owns the visibility decision, and this query just collects
     * the kinds the caller is allowed to see and filters rows accordingly.
     *
     * Iterating UserGameListType::cases() (rather than hard-coding Play and
     * AchievementSetRequest) means future kind-level visibility rules are a
     * policy-only change -- this query is unchanged.
     *
     * @param Relation<UserGameListEntry, User, mixed> $query
     * @return Relation<UserGameListEntry, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $caller = $request?->user();
        $target = $query->getParent();

        if (!$caller || !($target instanceof User)) {
            return $query->whereRaw('1 = 0');
        }

        $policy = new UserGameListEntryPolicy();
        $allowedKinds = [];

        foreach (UserGameListType::cases() as $kind) {
            if ($policy->view($caller, $target, $kind)) {
                $allowedKinds[] = $kind->value;
            }
        }

        return $query->whereIn('type', $allowedKinds);
    }
}
