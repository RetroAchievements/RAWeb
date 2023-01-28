<?php

declare(strict_types=1);

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
    ],
    [
        'name' => Role::DEVELOPER, // development access
        'display' => 0,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
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
    ],
    [
        'name' => Role::DEVELOPER_LEVEL_2, // dev
        'display' => 5,
        // 'permissions' => [
        //     'manage',
        //     'manageAchievements',
        //     'develop',
        // ],
    ],
    [
        'name' => Role::DEVELOPER_LEVEL_3, // jr. dev
        'display' => 5,
        // 'permissions' => [
        //     'manage',
        //     'develop',
        // ],
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
    ],
    [
        'name' => Role::FORUM_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageForums',
        // ],
        'staff' => true,
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
    ],
    [
        'name' => Role::NEWS_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageNews',
        // ],
        'staff' => true,
    ],
    [
        'name' => Role::TICKET_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageTickets',
        // ],
        'staff' => true,
    ],
    [
        'name' => Role::EVENT_MANAGER,
        'display' => 3,
        // 'permissions' => [
        //     'manage',
        //     'manageEvents',
        // ],
        'staff' => true,
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
    ],

    /*
     * vanity roles assigned by admin
     */
    [
        'name' => Role::DEVELOPER_VETERAN,
        'display' => 5,
    ],

    /*
     * vanity roles assigned by root
     */
    [
        'name' => Role::FOUNDER,
        'display' => 1,
        'staff' => true,
    ],
    [
        'name' => Role::ARCHITECT,
        'display' => 1,
        'staff' => true,
    ],
    [
        'name' => Role::ENGINEER,
        'display' => 1,
        'staff' => true,
    ],

    /*
     * access roles assigned by root
     */
    [
        'name' => Role::BETA,
        'display' => 0,
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
