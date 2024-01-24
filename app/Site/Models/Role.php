<?php

declare(strict_types=1);

namespace App\Site\Models;

use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;

class Role extends \Spatie\Permission\Models\Role
{
    /*
     * Providers Traits
     */
    use PivotEventTrait;

    // roles

    public const ROOT = 'root';

    // admin roles assigned by root

    public const ADMINISTRATOR = 'administrator';

    public const RELEASE_MANAGER = 'release-manager';

    // creator roles assigned by admin

    public const HUB_MANAGER = 'hub-manager';

    public const DEVELOPER_STAFF = 'developer-staff'; // staff

    public const DEVELOPER = 'developer';

    public const DEVELOPER_JUNIOR = 'developer-junior';

    public const ARTIST = 'artist';

    public const WRITER = 'writer';

    public const TESTER = 'tester';

    // moderation roles assigned by admin

    public const MODERATOR = 'moderator';

    public const FORUM_MANAGER = 'forum-manager';

    public const TICKET_MANAGER = 'ticket-manager';

    public const NEWS_MANAGER = 'news-manager';

    public const EVENT_MANAGER = 'event-manager';

    // vanity roles assigned by root

    public const FOUNDER = 'founder';

    public const ARCHITECT = 'architect';

    public const ENGINEER = 'engineer';

    // vanity roles assigned by admin

    public const DEVELOPER_VETERAN = 'developer-veteran';

    // vanity roles assigned by root

    public const BETA = 'beta';

    // public const SUPPORTER = 'supporter';

    // public const CONTRIBUTOR = 'contributor';

    public static function toFilamentColor(string $role): string
    {
        return match ($role) {
            Role::ROOT => 'gray',

            // admin roles assigned by root

            Role::ADMINISTRATOR => 'danger',
            Role::RELEASE_MANAGER => 'warning',

            // creator roles assigned by admin

            Role::HUB_MANAGER => 'warning',
            Role::DEVELOPER_STAFF => 'success',
            Role::DEVELOPER => 'success',
            Role::DEVELOPER_JUNIOR => 'success',
            Role::ARTIST => 'success',
            Role::WRITER => 'success',
            Role::TESTER => 'success',

            // moderation roles assigned by admin

            Role::MODERATOR => 'warning',
            Role::FORUM_MANAGER => 'info',
            Role::TICKET_MANAGER => 'info',
            Role::NEWS_MANAGER => 'info',
            Role::EVENT_MANAGER => 'info',

            // vanity roles assigned by root

            Role::FOUNDER => 'primary',
            Role::ARCHITECT => 'primary',
            Role::ENGINEER => 'primary',
            Role::BETA => 'primary',

            // vanity roles assigned by admin

            Role::DEVELOPER_VETERAN => 'primary',
            default => 'gray',
        };
    }

    public static function boot()
    {
        parent::boot();

        // record users role attach/detach in audit log

        static::pivotAttached(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            if ($relationName === 'users') {
                foreach ($pivotIds as $pivotId) {
                    $user = User::find($pivotId);
                    activity()->causedBy(auth()->user())->performedOn($user)
                        ->withProperty('relationships', ['roles' => [$model->id]])
                        ->withProperty('attributes', ['roles' => [$model->id => []]])
                        ->event('pivotAttached')
                        ->log('pivotAttached');
                }
            }
        });

        static::pivotDetached(function ($model, $relationName, $pivotIds) {
            if ($relationName === 'users') {
                foreach ($pivotIds as $pivotId) {
                    $user = User::find($pivotId);
                    activity()->causedBy(auth()->user())->performedOn($user)
                        ->withProperty('relationships', ['roles' => [$model->id]])
                        ->event('pivotDetached')
                        ->log('pivotDetached');
                }
            }
        });
    }

    public function getTitleAttribute(): string
    {
        return __('permission.role.' . $this->name);
    }
}
