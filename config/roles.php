<?php

use App\Site\Enums\Permissions;
use App\Site\Models\Role;

$rootAssignable = [
    Role::FOUNDER,
    Role::ARCHITECT,
    Role::RELEASE_MANAGER,
    Role::ADMINISTRATOR,
    Role::BETA,
];

$adminAssignable = [
    Role::MODERATOR,
    Role::FORUM_MANAGER,
    Role::HUB_MANAGER,
    Role::NEWS_MANAGER,
    Role::TICKET_MANAGER,
    Role::EVENT_MANAGER,
    Role::DEVELOPER_LEVEL_1,
    Role::DEVELOPER_LEVEL_2,
    Role::DEVELOPER_LEVEL_3,
    Role::DEVELOPER_VETERAN,
    // Role::DEVELOPER,
    Role::ENGINEER,
    Role::ARTIST,
];

$level1DevAssignable = [
    Role::DEVELOPER_LEVEL_2,
    Role::DEVELOPER_LEVEL_3,
];

/*
 * Note: permissions are not assigned to roles in database for now - check AuthServiceProvider
 */
return [
    /*
     * base roles
     */
    [
        'name' => Role::ROOT,
        'display' => 0,
        // 'permissions' => [
        //     'manage',
        //     'administrate',
        //     'moderate',
        // ],
        'assign' => array_merge($rootAssignable, $adminAssignable),
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::ADMINISTRATOR,
        'display' => 0,
        // 'permissions' => [
        //     'manage',
        //     'administrate',
        //     'moderate',
        // ],
        'assign' => $adminAssignable,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::DEVELOPER, // development access
        'display' => 0,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
        'legacy_role' => Permissions::JuniorDeveloper,
    ],
    [
        'name' => Role::DEVELOPER_LEVEL_1, // staff dev
        'display' => 4,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
        'assign' => $level1DevAssignable,
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::DEVELOPER_LEVEL_2, // dev
        'display' => 5,
        // 'permissions' => [
        //     'manage',
        //     'manageAchievements',
        //     'develop',
        // ],
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::DEVELOPER_LEVEL_3, // jr. dev
        'display' => 5,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
        'legacy_role' => Permissions::JuniorDeveloper,
    ],

    /*
     * staff roles
     */
    [
        'name' => Role::RELEASE_MANAGER,
        'display' => 0,
        // 'permissions' => [
        //     'manage',
        //     'manageReleases',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],

    /*
     * moderation roles
     */
    [
        'name' => Role::MODERATOR,
        'display' => 2,
        // 'permissions' => [
        //     'manage',
        //     'moderate',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::FORUM_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageForums',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::HUB_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageGames',
        //     'manageSystems',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::NEWS_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageNews',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::TICKET_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageTickets',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::EVENT_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageEvents',
        // ],
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],

    /*
     * creator roles
     */
    [
        'name' => Role::ARTIST,
        'display' => 5,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
        'legacy_role' => Permissions::JuniorDeveloper,
    ],

    /*
     * vanity roles assigned by admin
     */
    [
        'name' => Role::DEVELOPER_VETERAN,
        'display' => 5,
        'legacy_role' => Permissions::Developer,
    ],

    /*
     * vanity roles assigned by root
     */
    [
        'name' => Role::FOUNDER,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::ARCHITECT,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::ENGINEER,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],

    /*
     * access roles assigned by root
     */
    [
        'name' => Role::BETA,
        'display' => 0,
        'legacy_role' => Permissions::Registered,
    ],

    /*
     * assigned by system
     */
    // [
    //     'name' => Role::SUPPORTER,
    //     'display' => 5,
    // ],
    //
    // [
    //     'name' => Role::CONTRIBUTOR,
    //     'display' => 5,
    // ],
];
