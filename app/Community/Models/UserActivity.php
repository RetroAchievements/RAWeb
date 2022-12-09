<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends BaseModel
{
    protected $table = 'user_activity_log';

    public const Unknown = 0;

    public const UnlockedAchievement = 1;

    public const Login = 2;

    public const StartedPlaying = 3;

    public const UploadAchievement = 4;

    public const EditAchievement = 5;

    public const CompleteGame = 6;

    public const NewLeaderboardEntry = 7;

    public const ImprovedLeaderboardEntry = 8;

    public const OpenedTicket = 9;

    public const ClosedTicket = 10;

    protected $fillable = [
        'activity_type_id',
        'subject_type',
        'subject_id',
        'subject_context',
    ];

    protected $with = [
        'user',
    ];

    // == accessors

    /**
     * Needed to determine which icon to display in feed, polymorphic to ID stored in "data"
     */
    public function isAchievementActivity(): bool
    {
        return in_array($this->activity_type_id, [
            self::UnlockedAchievement,
            self::UploadAchievement,
            self::EditAchievement,
            self::OpenedTicket,
            self::ClosedTicket,
        ]);
    }

    /**
     * Needed to determine which icon to display in feed, polymorphic to ID stored in "data"
     */
    public function isGameActivity(): bool
    {
        return in_array($this->activity_type_id, [
            self::StartedPlaying,
            self::CompleteGame,
            self::NewLeaderboardEntry,
            self::ImprovedLeaderboardEntry,
        ]);
    }

    public function getIconAttribute(): ?string
    {
        $icon = null;
        switch ($this->activity_type_id) {
            case self::UnlockedAchievement:
                /*
                 * TODO
                 * $this->achievement->badge
                 */
                break;
            case self::Login:
                $icon = 'assets/images/activity/login.webp';
                break;
            case self::StartedPlaying:
                $icon = 'assets/images/activity/playing.webp';
                break;
        }

        return $icon;
    }

    public function getClassAttribute(): ?string
    {
        return $this->type ? str_replace('.', '-', $this->type) : null;
    }

    public function getTypeAttribute(): ?string
    {
        $type = null;
        $type = match ($this->activity_type_id) {
            self::UnlockedAchievement => 'achievement.unlock',
            self::Login => 'login',
            self::StartedPlaying => 'game.session.start',
            self::UploadAchievement => 'achievement.upload',
            default => $type,
        };

        return $type;
    }

    // == mutators

    // == relations

    // TODO: morph those
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'subject_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'subject_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
