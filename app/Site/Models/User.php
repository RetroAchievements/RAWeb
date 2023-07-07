<?php

declare(strict_types=1);

namespace App\Site\Models;

use App\Community\Concerns\ActsAsCommunityMember;
use App\Community\Contracts\CommunityMember;
use App\Community\Contracts\HasComments;
use App\Platform\Concerns\ActsAsDeveloper;
use App\Platform\Concerns\ActsAsPlayer;
use App\Platform\Concerns\CollectsBadges;
use App\Platform\Contracts\Developer;
use App\Platform\Contracts\Player;
use App\Site\Concerns\HasAccount;
use App\Site\Concerns\HasAvatar;
use App\Site\Concerns\HasPreferences;
use App\Site\Enums\Permissions;
use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use App\Support\HashId\HasHashId;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Optimus\Optimus;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

// TODO MustVerifyEmail,
class User extends Authenticatable implements CommunityMember, Developer, HasComments, HasLocalePreference, HasMedia, Player
{
    /*
     * Framework Traits
     */
    use HasFactory;
    use Notifiable;

    use Searchable;
    use SoftDeletes;

    /*
     * Providers Traits
     */
    use InteractsWithMedia;
    use HasRoles;

    // TODO use CausesActivity;

    /*
     * Shared Traits
     */
    use HasHashId;
    use HasFullTableName;

    /*
     * RA Feature Traits
     */
    use HasAccount;
    use HasAvatar;
    use HasPreferences;
    use ActsAsCommunityMember;
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
    // TODO drop Untracked in favor of unranked_at
    // TODO drop ManuallyVerified in favor of forum_verified_at
    // TODO drop SaltedPass in favor of Password
    // TODO drop Permissions in favor of RBAC tables
    // TODO rename ID column to id
    // TODO rename User column to username
    // TODO rename Password column to password
    // TODO rename EmailAddress column to email
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
        'motto',
        'password', // fillable for registration
        'preferences',
        'RAPoints',
        'RASoftcorePoints',
        'RichPresenceMsg',
        'RichPresenceMsgDate',
        'TrueRAPoints',
        'timezone',
        'unranked_at',
        'User', // fillable for registration
    ];

    protected $visible = [
        "ID",
        "User",
        "Permissions",
        "achievements_unlocked",
        "achievements_unlocked_hardcore",
        "completion_percentage_average",
        "completion_percentage_average_hardcore",
        "RAPoints",
        "RASoftcorePoints",
        "ContribCount",
        "ContribYield",
        "TrueRAPoints",
        "websitePrefs",
    ];

    protected $appends = [
        'avatarUrl',
    ];

    protected $casts = [
        'DeleteRequested' => 'datetime',
        'LastLogin' => 'datetime',
        'RichPresenceMsgDate' => 'datetime',
        'banned_at' => 'datetime',
        'appTokenExpiry' => 'datetime',
        'email_verified_at' => 'datetime',
        'muted_until' => 'datetime',
        'unranked_at' => 'datetime',
    ];

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

    // search

    public function toSearchableArray(): array
    {
        $searchable = $this->only([
            'ID',
            'User',
            'display_name',
        ]);

        /*
         * add trigrams of the username to the index to have partial matches show up as well
         */

        // $searchable['usernameNgrams'] = utf8_encode((new TNTIndexer())->buildTrigrams($this->username));

        return $searchable;
    }

    public function shouldBeSearchable(): bool
    {
        // TODO check privacy setting

        if ($this->banned_at) {
            return false;
        }

        // TODO return true;
        return false;
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->registerAvatarMediaCollection();
    }

    // == accessors

    public function preferredLocale()
    {
        return $this->locale;
    }

    public function getRouteKeyName(): string
    {
        /*
         * TODO: this might not hold up for changeable usernames -> find a better solution
         */
        return 'User';
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('user.show', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('user.permalink', $this->getHashIdAttribute());
    }

    protected function getHashIdAttribute(): int
    {
        return app(Optimus::class)->encode($this->getAttribute('ID'));
    }

    public function getDisplayNameAttribute(): ?string
    {
        // return $this->attributes['display_name'] ?? $this->attributes['username'] ?? null;
        return $this->getAttribute('User');
    }

    public function getUsernameAttribute(): string
    {
        return $this->getAttribute('User');
    }

    public function getAvatarUrlAttribute(): string
    {
        return media_asset('UserPic/' . $this->getAttribute('User') . '.png');
    }

    // Email verification

    public function hasVerifiedEmail(): bool
    {
        return (int) $this->getAttribute('Permissions') >= Permissions::Registered;
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

    // public function requestContext(Request $request)
    // {
    //     $this->sortBy = $request->get('sort');
    //     $this->sortDirection = $request->get('order');
    //     $this->page = (int)$request->get('page');
    //     $this->perPage = (int)$request->get('per_page', $this->perPage);
    //
    //     /**
    //      * translate legacy sort and order parameters
    //      * TODO: remove after release cooldown
    //      */
    //     if ($request->has('s')) {
    //         switch ($request->get('s')) {
    //             case 1:
    //                 $this->sortBy = 'username';
    //                 $this->sortDirection = 'asc';
    //                 break;
    //             case 4:
    //                 $this->sortBy = 'username';
    //                 $this->sortDirection = 'desc';
    //                 break;
    //             case 2:
    //                 $this->sortBy = 'points';
    //                 $this->sortDirection = 'desc';
    //                 break;
    //             case 5:
    //                 $this->sortBy = 'points';
    //                 $this->sortDirection = 'asc';
    //                 break;
    //             case 3:
    //                 $this->sortBy = 'achievements';
    //                 $this->sortDirection = 'desc';
    //                 break;
    //             case 6:
    //                 $this->sortBy = 'achievements';
    //                 $this->sortDirection = 'asc';
    //                 break;
    //         }
    //     }
    //
    //     /**
    //      * translate legacy offset parameter
    //      * TODO: remove after release cooldown
    //      */
    //     $offset = $request->get('o', 0);
    //     if ($offset) {
    //         $this->page = (int)(($offset - 1) / $this->perPage) + 1;
    //     }
    //
    //     return $this;
    // }

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
}
