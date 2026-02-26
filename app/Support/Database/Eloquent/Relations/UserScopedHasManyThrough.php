<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Solves a specific problem with HasManyThrough: when the final table
 * has rows for multiple users (eg: player_achievement_sets), a standard
 * HasManyThrough returns all users' rows for the matched intermediate keys.
 * We need to also filter by user_id, but Laravel's eager loading creates
 * a blank model to set up the query, so `$this->user_id` is always null.
 *
 * This class properly applies the user_id constraint in both the direct
 * access and eager loading paths.
 *
 * @template TRelatedModel of Model
 * @template TIntermediateModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
 */
class UserScopedHasManyThrough extends HasManyThrough
{
    public function addConstraints(): void
    {
        parent::addConstraints();

        // For direct (non-eager) access, scope to the parent model's user.
        if (static::$constraints) {
            $userId = $this->farParent->user_id ?? null;
            if ($userId !== null) {
                $this->query->where($this->related->getTable() . '.user_id', $userId);
            }
        }
    }

    /**
     * @param array<int, TDeclaringModel> $models
     */
    public function addEagerConstraints(array $models): void
    {
        parent::addEagerConstraints($models);

        // All parent models share the same user_id in a user-scoped request.
        $userId = $models[0]->user_id ?? null;
        if ($userId !== null) {
            $this->query->where($this->related->getTable() . '.user_id', $userId);
        }
    }
}
