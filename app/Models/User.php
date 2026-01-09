<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\ActsAsCommunityMember;
use App\Community\Contracts\CommunityMember;
use App\Community\Contracts\HasComments;
use App\Concerns\HasAccount;
use App\Concerns\HasAvatar;
use App\Concerns\HasPreferences;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Platform\Concerns\ActsAsDeveloper;
use App\Platform\Concerns\ActsAsPlayer;
use App\Platform\Concerns\CollectsBadges;
use App\Platform\Concerns\HasConnectToken;
use App\Platform\Contracts\Developer;
use App\Platform\Contracts\Player;
use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use Database\Factories\UserFactory;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

// TODO MustVerifyEmail
// TODO HasComments
class User extends Authenticatable implements CommunityMember, Developer, HasLocalePreference, HasMedia, Player, FilamentUser, HasName, OAuthenticatable
{
    /*
     * Framework Traits
     */
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use Searchable;
    use SoftDeletes;
    use HasApiTokens;

    /*
     * Providers Traits
     */
    use PivotEventTrait;
    use HasRoles;
    use InteractsWithMedia;

    use CausesActivity;
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    /*
     * Shared Traits
     */
    use HasFullTableName;

    /*
     * RA Feature Traits
     */
    use HasAccount;
    use HasAvatar;
    use HasConnectToken;
    use HasPreferences;
    use ActsAsCommunityMember {
        ActsAsCommunityMember::activities insteadof LogsActivity;
    }
    use ActsAsDeveloper;
    use ActsAsPlayer;

    // use CausesModerationIncidents;
    use CollectsBadges;

    // TODO drop Permissions in favor of auth_roles
    // TODO drop ManuallyVerified in favor of forum_verified_at
    protected $table = 'users';

    protected $fillable = [
        'achievements_hardcore_total',
        'achievements_total',
        'banned_at',
        'connect_token',
        'connect_token_expires_at',
        'country',
        'display_name',
        'email', // fillable for registration
        'email_verified_at',
        'is_user_wall_active',
        'last_activity_at',
        'legacy_salted_password', // fillable for when users are banned
        'locale',
        'locale_date',
        'locale_number',
        'locale_time',
        'ManuallyVerified',
        'motto',
        'muted_until',
        'password', // fillable for registration
        'PasswordResetToken', // fillable for when users are banned
        'Permissions',
        'points',
        'points_hardcore',
        'points_weighted',
        'preferences',
        'preferences_bitfield',
        'rich_presence',
        'rich_presence_updated_at',
        'timezone',
        'unranked_at',
        'username', // fillable for registration
        'visible_role_id',
        'web_api_calls',
        'web_api_key',
        'yield_points',
        'yield_unlocks',
    ];

    protected $visible = [
        "achievements_unlocked",
        "achievements_unlocked_hardcore",
        "avatarUrl",
        "completion_percentage_average",
        "completion_percentage_average_hardcore",
        "created_at",
        "deleted_at",
        "display_name",
        "id",
        "is_user_wall_active",
        "isMuted",
        "last_activity_at",
        "ManuallyVerified",
        "motto",
        "Permissions",
        "points",
        "points_hardcore",
        "points_weighted",
        "preferences_bitfield",
        "unranked_at",
        "unread_messages",
        "username",
        "yield_points",
        "yield_unlocks",
    ];

    protected $appends = [
        'avatarUrl',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
        'connect_token_expires_at' => 'datetime',
        'delete_requested_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'is_user_wall_active' => 'boolean',
        'last_activity_at' => 'datetime',
        'muted_until' => 'datetime',
        'password' => 'hashed',
        'Permissions' => 'integer',
        'points' => 'integer',
        'points_hardcore' => 'integer',
        'points_weighted' => 'integer',
        'rich_presence_updated_at' => 'datetime',
        'unranked_at' => 'datetime',
        'yield_points' => 'integer',
        'yield_unlocks' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        // record users role attach/detach in audit log

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'roles') {
                $user = Auth::user();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => null])
                    ->withProperty('attributes', [$relationName => (new Collection($pivotIds))
                        ->map(fn ($pivotId) => [
                            'id' => $pivotId,
                            'attributes' => $pivotIdsAttributes[$pivotId],
                        ]),
                    ])
                    ->event('pivotAttached')
                    ->log('pivotAttached');
            }
        });

        static::pivotDetached(function ($model, $relationName, $pivotIds) {
            if ($relationName === 'roles') {
                $user = Auth::user();

                activity()->causedBy($user)->performedOn($model)
                    ->withProperty('old', [$relationName => (new Collection($pivotIds))
                        ->map(fn ($pivotId) => [
                            'id' => $pivotId,
                        ]),
                    ])
                    ->withProperty('attributes', [$relationName => null])
                    ->event('pivotDetached')
                    ->log('pivotDetached');
            }
        });
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    // Filament

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('accessManagementTools');
    }

    public function getFilamentName(): string
    {
        return $this->display_name;
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'banned_at',
                'country',
                'display_name',
                'email_verified_at',
                'is_user_wall_active',
                'locale',
                'locale_date',
                'locale_number',
                // 'locale_time',
                'ManuallyVerified',
                'motto',
                'muted_until',
                'timezone',
                'unranked_at',
                'username',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        return [
            'display_name' => $this->display_name,
            'last_activity_at' => $this->last_activity_at,
            'username' => $this->username,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        if (isset($this->banned_at)) {
            return false;
        }

        return true;
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->registerAvatarMediaCollection();
    }

    // == actions

    /**
     * @return Builder<static>
     */
    public static function whereName(?string $displayNameOrUsername): Builder
    {
        if ($displayNameOrUsername === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()
            ->where(function ($query) use ($displayNameOrUsername) {
                $query->where('display_name', $displayNameOrUsername)
                    ->orWhere('username', $displayNameOrUsername);
            });
    }

    // == accessors

    public function preferredLocale()
    {
        return $this->locale;
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        return $this->where('display_name', $value)
            ->orWhere('username', $value)
            ->withTrashed()
            ->firstOrFail();
    }

    public function getRouteKey(): string
    {
        return $this->display_name;
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where('display_name', $value)
            ->orWhere('username', $value)
            ->firstOrFail();
    }

    public function isModerated(): bool
    {
        return
            $this->muted_until?->isFuture()
            || $this->unranked_at !== null
            || $this->banned_at !== null
            || $this->delete_requested_at !== null
        ;
    }

    public function isNew(): bool
    {
        return Carbon::now()->diffInMonths($this->created_at, true) < 1;
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('user.show', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('user.permalink', $this->ulid);
    }

    public function getAvatarUrlAttribute(): string
    {
        return media_asset('UserPic/' . $this->username . '.png');
    }

    public function getPermissionsAttribute(): int
    {
        return $this->attributes['Permissions'];
    }

    public function getEnableBetaFeaturesAttribute(): bool
    {
        return BitSet($this->preferences_bitfield, UserPreference::User_EnableBetaFeatures);
    }

    public function getShouldAlwaysBypassContentWarningsAttribute(): bool
    {
        return BitSet($this->preferences_bitfield, UserPreference::Site_SuppressMatureContentWarning);
    }

    public function getPrefersAbsoluteDatesAttribute(): bool
    {
        return BitSet($this->preferences_bitfield, UserPreference::Forum_ShowAbsoluteDates);
    }

    public function getIsGloballyOptedOutOfSubsetsAttribute(): bool
    {
        return BitSet($this->preferences_bitfield, UserPreference::Game_OptOutOfAllSubsets);
    }

    public function getOnlyAllowsContactFromFollowersAttribute(): bool
    {
        return BitSet($this->preferences_bitfield, UserPreference::User_OnlyContactFromFollowing);
    }

    // Email verification

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at?->isPast() ?? false;
    }

    public function markEmailAsVerified(): bool
    {
        return true;
    }

    public function sendEmailVerificationNotification(): void
    {
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<UserModerationAction, $this>
     */
    public function moderationActions(): HasMany
    {
        return $this->hasMany(UserModerationAction::class, 'user_id');
    }

    // == scopes

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeHasAnyPoints(Builder $query): Builder
    {
        return $query->where('points_hardcore', '>', 0)
            ->orWhere('points', '>', 0);
    }

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('displayableRoles', fn ($q) => $q->where('name', $role));
    }
}
