<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Models\EventAchievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Eloquent\Contracts\Driver;
use LaravelJsonApi\Eloquent\Contracts\Parser;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Schema;

class EventAchievementRepository extends Repository
{
    private Parser $eventAchievementParser;
    private Schema $eventAchievementSchema;

    public function __construct(Schema $schema, Driver $driver, Parser $parser)
    {
        parent::__construct($schema, $driver, $parser);
        $this->eventAchievementParser = $parser;
        $this->eventAchievementSchema = $schema;
    }

    public function find(string $resourceId): ?object
    {
        $eventAchievement = $this->queryForCaller()
            ->find($resourceId);

        return $eventAchievement ? $this->eventAchievementParser->parseNullable($eventAchievement) : null;
    }

    public function exists(string $resourceId): bool
    {
        return $this->queryForCaller()
            ->whereKey($resourceId)
            ->exists();
    }

    /**
     * @return Builder<EventAchievement>
     */
    private function queryForCaller(): Builder
    {
        $caller = Auth::user();
        $user = $caller instanceof User ? $caller : null;

        $query = EventAchievement::query()
            ->with($this->eventAchievementSchema->with());

        if (!$user?->can('manage', EventAchievement::class)) {
            $query->promoted();
        }

        return $query;
    }
}
