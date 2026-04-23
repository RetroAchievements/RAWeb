<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotResolutionService;
use App\Policies\GameScreenshotPolicy;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameScreenshotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GameScreenshot extends BaseModel
{
    /** @use HasFactory<GameScreenshotFactory> */
    use HasFactory;
    use SortableTrait;

    protected $table = 'game_screenshots';

    protected $fillable = [
        'game_id',
        'media_id',
        'width',
        'height',
        'type',
        'is_primary',
        'status',
        'description',
        'captured_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
        'rejection_notes',
    ];

    protected $casts = [
        'type' => ScreenshotType::class,
        'status' => GameScreenshotStatus::class,
        'is_primary' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'reviewed_at' => 'datetime',
        'rejection_reason' => GameScreenshotRejectionReason::class,
    ];

    /** @var array<string, mixed> */
    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected static function newFactory(): GameScreenshotFactory
    {
        return GameScreenshotFactory::new();
    }

    // == accessors

    /**
     * @return Attribute<bool, never>
     */
    protected function hasWrongResolution(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (!$this->width || !$this->height) {
                    return false;
                }

                $system = $this->game?->system;
                if (empty($system?->screenshot_resolutions)) {
                    return false;
                }

                $service = app(ScreenshotResolutionService::class);

                return !$service->isValidResolution($this->width, $this->height, $system);
            },
        );
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    // == scopes

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', GameScreenshotStatus::Approved);
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public function scopeOfType(Builder $query, ScreenshotType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * This is separate, but complementary, to GameScreenshotPolicy
     * stuff because we actually need to filter a query by these values.
     *
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public function scopeReviewableBy(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(GameScreenshotPolicy::REVIEWER_ROLES)) {
            return $query;
        }

        if ($user->hasRole(Role::DEVELOPER)) {
            return $query->whereIn('game_id', Achievement::query()
                ->select('game_id')
                ->where('user_id', $user->id)
                ->where('is_promoted', true)
            );
        }

        return $query->whereKey(null);
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public function scopeOrderByType(Builder $query): Builder
    {
        return $query->orderByRaw("FIELD(type, ?, ?, ?)", [
            ScreenshotType::Title->value,
            ScreenshotType::Ingame->value,
            ScreenshotType::Completion->value,
        ]);
    }
}
