<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOneThrough;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class EventAchievementSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = EventAchievement::class;

    /**
     * Default pagination parameters when client doesn't provide any.
     * This prevents unbounded result sets.
     */
    protected ?array $defaultPagination = ['number' => 1];

    /**
     * Default sort order when client doesn't provide any.
     * Shows the most recently activated achievements first.
     */
    protected $defaultSort = '-activeFrom';

    /**
     * Relationships that should always be eager loaded.
     */
    protected array $with = ['event.legacyGame', 'sourceAchievement', 'achievement'];

    /**
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'event-achievements';
    }

    public function repository(): EventAchievementRepository
    {
        return new EventAchievementRepository(
            $this,
            $this->driver(),
            $this->parser(),
        );
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            DateTime::make('activeFrom', 'active_from')->sortable()->readOnly(),
            DateTime::make('activeUntil', 'active_until')->sortable()->readOnly(),
            Str::make('decorator')->readOnly(),

            HasOneThrough::make('event')->type('events'),
            BelongsTo::make('sourceAchievement')->type('achievements')->readOnly(),
            BelongsTo::make('eventAchievement', 'achievement')->type('achievements')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            new EventAchievementActiveFilter(),
            new EventAchievementEventIdFilter(),
            new EventAchievementEvergreenFilter(),
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
     * @param Builder<EventAchievement> $query
     * @return Builder<EventAchievement>
     */
    public function indexQuery(?object $model, Builder $query): Builder
    {
        /** @var User|null $user */
        $user = request()->user();

        return $query->visibleTo($user);
    }

    /**
     * @param Relation<EventAchievement, Event, mixed> $query
     * @return Relation<EventAchievement, Event, mixed>
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        /** @var User|null $user */
        $user = $request?->user();

        $query->visibleTo($user);

        return $query;
    }
}
