<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Community\Enums\UserActivityType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'subject_context',
    ];

    protected static function newFactory(): UserActivityFactory
    {
        return UserActivityFactory::new();
    }

    // == accessors

    /**
     * Needed to determine which icon to display in feed, polymorphic to ID stored in "data"
     */
    public function isAchievementActivity(): bool
    {
        return in_array($this->type, [
            UserActivityType::UnlockedAchievement,
            UserActivityType::UploadAchievement,
            UserActivityType::EditAchievement,
            UserActivityType::OpenedTicket,
            UserActivityType::ClosedTicket,
        ]);
    }

    /**
     * Needed to determine which icon to display in feed, polymorphic to ID stored in "data"
     */
    public function isGameActivity(): bool
    {
        return in_array($this->type, [
            UserActivityType::StartedPlaying,
            UserActivityType::CompleteAchievementSet,
            UserActivityType::NewLeaderboardEntry,
            UserActivityType::ImprovedLeaderboardEntry,
        ]);
    }

    public function getIconAttribute(): ?string
    {
        $icon = null;
        switch ($this->activitytype) {
            case UserActivityType::UnlockedAchievement:
                /*
                 * TODO
                 * $this->achievement->badge
                 */
                break;
            case UserActivityType::Login:
                $icon = 'assets/images/activity/login.webp';
                break;
            case UserActivityType::StartedPlaying:
                $icon = 'assets/images/activity/playing.webp';
                break;
        }

        return $icon;
    }

    public function getClassAttribute(): ?string
    {
        return $this->type ? str_replace('.', '-', $this->type) : null;
    }

    // == mutators

    // == relations

    // TODO: morph those
    /**
     * @return BelongsTo<Achievement, UserActivity>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'subject_id');
    }

    /**
     * @return BelongsTo<Game, UserActivity>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'subject_id');
    }

    /**
     * @return BelongsTo<User, UserActivity>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
