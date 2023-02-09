<?php

declare(strict_types=1);

namespace App\Site\Models;

use App\Community\Concerns\ActsAsCommunityMember;
use App\Community\Contracts\CommunityMember;
use App\Community\Contracts\HasComments;
use App\Platform\Concerns\ActsAsDeveloper;
use App\Platform\Concerns\ActsAsPlayer;
use App\Platform\Concerns\CollectsBadges;
use App\Platform\Concerns\UsesWebApi;
use App\Platform\Contracts\Developer;
use App\Platform\Contracts\Player;
use App\Site\Concerns\HasAccount;
use App\Site\Concerns\HasAvatar;
use App\Site\Concerns\HasPreferences;
use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use App\Support\HashId\HasHashId;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CommunityMember, Developer, HasComments, HasLocalePreference, HasMedia, MustVerifyEmail, Player
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
    use CausesActivity;
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
    use UsesWebApi;

    protected $fillable = [
        'api_calls',
        'api_token',
        'achievements_total',
        'achievements_hardcore_total',
        'achievements_unlocked_yield',
        'achievements_points_yield',
        'banned_at',
        'country',
        'display_name',
        'email', // fillable for registration
        'email_verified_at',
        'connect_token',
        'connect_token_expires_at',
        // 'last_activity_at',
        'last_login_at',
        'locale',
        'locale_date',
        'locale_time',
        'locale_number',
        'motto',
        'password', // fillable for registration
        'points_total',
        'preferences',
        'rich_presence',
        'rich_presence_updated_at',
        'points_weighted',
        'timezone',
        'unranked_at',
        'username', // fillable for registration
    ];

    protected $hidden = [
        'api_token',
        'media',
        'password',
        'remember_token',
        'roles',
        'connect_token',
        'connect_token_expires_at',
        'preferences',
    ];

    protected $appends = [
        'avatarUrl',
    ];

    protected $with = [
        'media',
        'roles',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
        'connect_token_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_login_at' => 'datetime',
        'muted_until' => 'datetime',
        'rich_presence_updated_at' => 'datetime',
        'unranked_at' => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        $searchable = $this->only([
            'id',
            'username',
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
        /*
         * TODO: check privacy setting
         */
        if ($this->banned_at) {
            return false;
        }

        return true;
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
        return 'username';
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('user.show', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('user.permalink', $this->getHashIdAttribute());
    }

    public function getDisplayNameAttribute(): ?string
    {
        return $this->attributes['display_name'] ?? $this->attributes['username'] ?? null;
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
}
