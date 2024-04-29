<?php

use App\Enums\Permissions;
use App\Models\Role;

$rootAssignable = [
    Role::ADMINISTRATOR,
    Role::ARCHITECT,
    Role::BETA,
    Role::ENGINEER,
    Role::FOUNDER,
    Role::RELEASE_MANAGER,
];

$adminAssignable = [
    Role::ARTIST,
    Role::DEVELOPER_JUNIOR,
    Role::DEVELOPER_STAFF,
    Role::DEVELOPER_VETERAN,
    Role::DEVELOPER,
    Role::EVENT_MANAGER,
    Role::FORUM_MANAGER,
    Role::GAME_EDITOR,
    Role::GAME_HASH_MANAGER,
    Role::MODERATOR,
    Role::NEWS_MANAGER,
    Role::PLAY_TESTER,
    Role::TICKET_MANAGER,
    Role::WRITER,
    Role::CHEAT_INVESTIGATOR,
];

$staffDevAssignable = [
    Role::DEVELOPER,
    Role::DEVELOPER_JUNIOR,
];

/*
 * Note: permissions are not assigned to roles in database for now - check AuthServiceProvider
 */
return [
    [
        'name' => Role::ROOT,
        'display' => 0,
        'assign' => array_merge($rootAssignable, $adminAssignable),
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],

    // admin roles assigned by root

    [
        'name' => Role::ADMINISTRATOR,
        'display' => 0,
        'assign' => $adminAssignable,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::RELEASE_MANAGER,
        'display' => 0,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],

    // creator roles assigned by admin

    [
        'name' => Role::GAME_HASH_MANAGER,
        'display' => 3,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::DEVELOPER_STAFF, // staff dev
        'display' => 4,
        'assign' => $staffDevAssignable,
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::DEVELOPER, // dev
        'display' => 5,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::DEVELOPER_JUNIOR, // jr. dev
        'display' => 5,
        'legacy_role' => Permissions::JuniorDeveloper,
    ],
    [
        'name' => Role::ARTIST,
        'display' => 5,
        'legacy_role' => Permissions::JuniorDeveloper,
    ],
    [
        'name' => Role::WRITER,
        'display' => 5,
        'legacy_role' => Permissions::JuniorDeveloper,
    ],
    [
        'name' => Role::GAME_EDITOR,
        'display' => 5,
        'legacy_role' => Permissions::JuniorDeveloper,
    ],
    [
        'name' => Role::PLAY_TESTER,
        'display' => 5,
        'legacy_role' => Permissions::Registered,
    ],

    // moderation roles assigned by admin

    [
        'name' => Role::MODERATOR,
        'display' => 2,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::FORUM_MANAGER,
        'display' => 3,
        'staff' => true,
        'legacy_role' => Permissions::Moderator,
    ],
    [
        'name' => Role::TICKET_MANAGER,
        'display' => 3,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::NEWS_MANAGER,
        'display' => 3,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::EVENT_MANAGER,
        'display' => 3,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::CHEAT_INVESTIGATOR,
        'display' => 0,
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],

    // vanity roles assigned by root

    [
        'name' => Role::FOUNDER,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::ARCHITECT,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::ENGINEER,
        'display' => 1,
        'staff' => true,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::BETA,
        'display' => 0,
        'legacy_role' => Permissions::Registered,
    ],

    // vanity roles assigned by admin

    [
        'name' => Role::DEVELOPER_VETERAN,
        'display' => 5,
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
