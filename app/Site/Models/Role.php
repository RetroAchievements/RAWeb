<?php

declare(strict_types=1);

namespace App\Site\Models;

class Role extends \Spatie\Permission\Models\Role
{
    /**
     * legacy role levels
     */
    // const Spam = -2;
    // const Banned = -1;
    // const Unregistered = 0;
    // const Registered = 1;
    // const JrDeveloper = 2;
    // const Developer = 3;
    // const Admin = 4;
    // const Root = 5;

    public const ROOT = 'root';

    /**
     * staff roles
     */
    public const ADMINISTRATOR = 'administrator';

    public const MODERATOR = 'moderator';

    public const STAFF = 'staff';

    /**
     * managers
     */
    // public const COMMUNITY_MANAGER = 'community-manager';

    public const EVENT_MANAGER = 'event-manager';

    public const FORUM_MANAGER = 'forum-manager';

    public const HUB_MANAGER = 'hub-manager';

    public const NEWS_MANAGER = 'news-manager';

    public const RELEASE_MANAGER = 'release-manager';

    public const TICKET_MANAGER = 'ticket-manager';

    /**
     * creator roles
     */
    public const DEVELOPER = 'developer';

    public const DEVELOPER_LEVEL_1 = 'developer-level-1'; // staff dev

    public const DEVELOPER_LEVEL_2 = 'developer-level-2'; // dev

    public const DEVELOPER_LEVEL_3 = 'developer-level-3'; // jr. dev

    public const ARTIST = 'artist';

    public const WRITER = 'writer';

    // public const TESTER = 'tester';

    /**
     * vanity roles
     * assigned by admins
     */
    public const DEVELOPER_VETERAN = 'developer-veteran';

    /**
     * vanity roles
     * assigned by root
     */
    public const FOUNDER = 'founder';

    public const ARCHITECT = 'architect';

    public const ENGINEER = 'engineer';

    /**
     * vanity roles
     * assigned by root
     */
    public const BETA = 'beta';

    // public const SUPPORTER = 'supporter';

    // public const CONTRIBUTOR = 'contributor';
}
