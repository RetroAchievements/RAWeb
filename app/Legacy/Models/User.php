<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Carbon;
use Jenssegers\Optimus\Optimus;

/**
 * @property Carbon $DeleteRequested
 * @property string $EmailAddress
 * @property int $ID
 * @property int $Permissions
 * @property int $RAPoints
 * @property int $RASoftcorePoints
 * @property int $TrueRAPoints
 * @property int $UnreadMessageCount
 * @property string $User
 * @property int $websitePrefs
 */
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable;
    use Authorizable;
    use HasFullTableName;
    use SoftDeletes;

    protected $table = 'UserAccounts';

    public const DELETED_AT = 'Deleted';

    protected $dates = [
        'DeleteRequested',
        'LastLogin',
        'RichPresenceMsgDate',
    ];

    protected $hidden = [
        'APIKey',
        'appToken',
        'appTokenExpiry',
        'cookie',
        'fbUser',
        'fbPrefs',
        'Password',
        'PasswordResetToken',
        'SaltedPass',
    ];

    public function getAuthPassword()
    {
        return $this->Password;
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

    protected function getHashIdAttribute(): int
    {
        return app(Optimus::class)->encode($this->getAttribute('ID'));
    }

    // v2 attribute shims

    public function getDisplayNameAttribute(): string
    {
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
}
