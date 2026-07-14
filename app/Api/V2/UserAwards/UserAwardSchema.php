<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Models\PlayerBadge;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserAwardSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PlayerBadge::class;

    protected int $maxDepth = 2;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Mirrors the existing profile award ordering.
     *
     * @var array<int, string>
     */
    protected $defaultSort = ['displayOrder', 'awardedAt'];

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = [
        'eventIfApplicable',
        'eventIfApplicable.awards',
        'eventIfApplicable.legacyGame',
        'gameIfApplicable',
        'gameIfApplicable.system',
        'siteAwardIfApplicable',
    ];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'user-awards';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('awardedAt', 'awarded_at')->sortable()->readOnly(),
            Str::make('kind', 'award_type')->readOnly(),
            Str::make('title', 'award_key')->readOnly(),
            Str::make('badgeUrl', 'award_key')->readOnly(),
            Number::make('displayOrder', 'order_column')->sortable()->readOnly(),
            ArrayHash::make('context', 'award_key')->readOnly(),

            BelongsTo::make('event', 'eventIfApplicable')->type('events')->readOnly(),
            BelongsTo::make('game', 'gameIfApplicable')->type('games')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Scope::make('awardedFrom'),
            Scope::make('awardedTo'),
            Scope::make('eventId', 'forEventId'),
            Scope::make('gameId', 'forGameId'),
            new UserAwardGameAwardTierFilter(),
            new UserAwardKindFilter(),
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
     * @param Relation<PlayerBadge, User, mixed> $query
     * @return Relation<PlayerBadge, User, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        $parent = $query->getParent();

        if ($parent instanceof User) {
            return $query
                ->canonicalForApiUser($parent->id)
                ->visibleOnUserProfile();
        }

        return $query;
    }
}
