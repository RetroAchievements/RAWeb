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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

// TODO MustVerifyEmail,
// TODO HasComments,
class User extends Authenticatable implements CommunityMember, Developer, HasLocalePreference, HasMedia, Player, FilamentUser, HasName
{
    /*
     * Framework Traits
     */
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use Searchable;

    use SoftDeletes;

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
    use HasPreferences;
    use ActsAsCommunityMember {
        ActsAsCommunityMember::activities insteadof LogsActivity;
    }
    use ActsAsDeveloper;
    use ActsAsPlayer;

    // use CausesModerationIncidents;
    use CollectsBadges;

    // TODO use UsesWebApi;

    // TODO rename UserAccounts table to users
    // TODO drop cookie, fbUser, fbPrefs, LastActivityID, LastGameID, PasswordResetToken, UnreadMessageCount
    // TODO drop RichPresenceMsg, RichPresenceMsgDate -> player_sessions
    // TODO drop LastActivityID, LastGameID, UnreadMessageCount -> derived
    // TODO drop PasswordResetToken -> password_resets table
    // TODO move UserWallActive to preferences, allow comments to be visible to/writable for public, friends, private etc
    // TODO rename Untracked to unranked or drop in favor of unranked_at (update indexes)
    // TODO drop ID index
    // TODO remove User from PRIMARY, there's already a unique index on username (User)
    // TODO drop ManuallyVerified in favor of forum_verified_at
    // TODO drop SaltedPass in favor of Password
    // TODO drop Permissions in favor of RBAC tables
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename User column to username
    // TODO rename Password column to password
    // TODO rename EmailAddress column to email, remove getEmailAttribute()
    // TODO rename LastLogin column to last_activity_at
    // TODO rename appToken column to connect_token or to passport
    // TODO rename appTokenExpiry column to connect_token_expires_at or to passport
    // TODO rename APIKey column to api_token or to passport
    // TODO rename APIUses column to api_calls or to passport
    // TODO rename RAPoints column to points
    // TODO rename TrueRAPoints column to points_weighted
    // TODO rename RASoftcorePoints column to points_softcore
    // TODO rename ContribCount column to yield_unlocks
    // TODO rename ContribYield column to yield_points
    // TODO introduce unique email addresses
    protected $table = 'UserAccounts';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';
    public const DELETED_AT = 'Deleted';

    protected $fillable = [
        'achievements_total',
        'achievements_hardcore_total',
        'APIUses',
        'APIKey',
        'banned_at',
        'cookie', // fillable for when users are banned
        'ContribCount',
        'ContribYield',
        'country',
        'display_name',
        'EmailAddress', // fillable for registration
        'email_verified_at',
        'appToken',
        'appTokenExpiry',
        'LastLogin',
        'locale',
        'locale_date',
        'locale_time',
        'locale_number',
        'ManuallyVerified',
        'Motto',
        'muted_until',
        'password', // fillable for registration
        'PasswordResetToken', // fillable for when users are banned
        'Permissions',
        'preferences',
        'RAPoints',
        'RASoftcorePoints',
        'RichPresenceMsg',
        'RichPresenceMsgDate',
        'SaltedPass', // fillable for when users are banned
        'TrueRAPoints',
        'timezone',
        'unranked_at',
        'Untracked',
        'User', // fillable for registration
        'UserWallActive',
        'visible_role_id',
        'websitePrefs',
    ];

    protected $visible = [
        "achievements_unlocked_hardcore",
        "achievements_unlocked",
        "avatarUrl",
        "completion_percentage_average_hardcore",
        "completion_percentage_average",
        "ContribCount",
        "ContribYield",
        "Created",
        "Deleted",
        'display_name',
        "ID",
        "isMuted",
        "LastLogin",
        "ManuallyVerified",
        "Motto",
        "Permissions",
        "RAPoints",
        "RASoftcorePoints",
        "TrueRAPoints",
        "UnreadMessageCount",
        "Untracked",
        "User",
        "UserWallActive",
        "websitePrefs",
    ];

    protected $appends = [
        'avatarUrl',
    ];

    protected $casts = [
        'appTokenExpiry' => 'datetime',
        'banned_at' => 'datetime',
        'ContribCount' => 'integer',
        'ContribYield' => 'integer',
        'DeleteRequested' => 'datetime',
        'email_verified_at' => 'datetime',
        'LastLogin' => 'datetime',
        'muted_until' => 'datetime',
        'password' => 'hashed',
        'Permissions' => 'integer',
        'RAPoints' => 'integer',
        'RASoftcorePoints' => 'integer',
        'RichPresenceMsgDate' => 'datetime',
        'TrueRAPoints' => 'integer',
        'unranked_at' => 'datetime',
        'UserWallActive' => 'boolean',
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

        // When a user is restored, check if they should remain unranked.
        static::restored(function (User $user) {
            if ($user->unranked_at === null) {
                UnrankedUser::where('user_id', $user->id)->delete();
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
        return $this->Password;
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
                'locale',
                'locale_date',
                'locale_number',
                // 'locale_time',
                'ManuallyVerified',
                'Motto',
                'muted_until',
                'timezone',
                'unranked_at',
                'Untracked',
                'User',
                'UserWallActive',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        return [
            'display_name' => $this->display_name,
            'last_activity_at' => $this->LastLogin,
            'username' => $this->username,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        if (isset($this->Deleted) || isset($this->banned_at)) {
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
     * @return Builder<User>
     */
    public static function whereName(?string $displayNameOrUsername): Builder
    {
        if ($displayNameOrUsername === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()
            ->where(function ($query) use ($displayNameOrUsername) {
                $query->where('display_name', $displayNameOrUsername)
                    ->orWhere('User', $displayNameOrUsername);
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
            ->orWhere('User', $value)
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
            ->orWhere('User', $value)
            ->firstOrFail();
    }

    public function isModerated(): bool
    {
        return
            $this->muted_until?->isFuture()
            || $this->unranked_at !== null
            || $this->banned_at !== null
            || $this->DeleteRequested !== null
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
        return media_asset('UserPic/' . $this->getAttribute('User') . '.png');
    }

    // TODO remove after rename
    public function getIdAttribute(): ?int
    {
        return $this->attributes['ID'] ?? null;
    }

    // TODO remove after rename
    public function getEmailAttribute(): string
    {
        return $this->attributes['EmailAddress'];
    }

    // TODO remove after rename
    public function getCreatedAtAttribute(): Carbon
    {
        return $this->attributes['Created']
            ? Carbon::parse($this->attributes['Created'])
            : Carbon::now(); // Created is currently nullable
    }

    public function getUsernameAttribute(): string
    {
        return $this->getAttribute('User');
    }

    public function getPermissionsAttribute(): int
    {
        return $this->attributes['Permissions'];
    }

    public function getShouldAlwaysBypassContentWarningsAttribute(): bool
    {
        return BitSet($this->getAttribute('websitePrefs'), UserPreference::Site_SuppressMatureContentWarning);
    }

    public function getPrefersAbsoluteDatesAttribute(): bool
    {
        return BitSet($this->getAttribute('websitePrefs'), UserPreference::Forum_ShowAbsoluteDates);
    }

    public function getIsGloballyOptedOutOfSubsetsAttribute(): bool
    {
        return BitSet($this->getAttribute('websitePrefs'), UserPreference::Game_OptOutOfAllSubsets);
    }

    public function getOnlyAllowsContactFromFollowersAttribute(): bool
    {
        return BitSet($this->getAttribute('websitePrefs'), UserPreference::User_OnlyContactFromFollowing);
    }

    public function getLastActivityAtAttribute(): string
    {
        return $this->getAttribute('LastLogin');
    }

    public function getPointsAttribute(): int
    {
        return (int) $this->getAttribute('RAPoints');
    }

    public function getPointsSoftcoreAttribute(): int
    {
        return (int) $this->getAttribute('RASoftcorePoints');
    }

    public function getPointsWeightedAttribute(): int
    {
        return (int) $this->getAttribute('TrueRAPoints');
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
        return $this->EmailAddress;
    }

    // == mutators

    // == relations

    // == scopes

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeHasAnyPoints(Builder $query): Builder
    {
        return $query->where('RAPoints', '>', 0)
            ->orWhere('RASoftcorePoints', '>', 0);
    }

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('Permissions', '>', 0);
    }
}
