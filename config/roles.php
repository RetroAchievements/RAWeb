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
    Role::TEAM_ACCOUNT,
];

$adminAssignable = [
    Role::ARTIST,
    Role::CHEAT_INVESTIGATOR,
    Role::CODE_REVIEWER,
    Role::COMMUNITY_MANAGER,
    Role::DEV_COMPLIANCE,
    Role::DEVELOPER_JUNIOR,
    Role::DEVELOPER_RETIRED,
    Role::DEVELOPER,
    Role::EVENT_MANAGER,
    Role::FORUM_MANAGER,
    Role::GAME_EDITOR,
    Role::GAME_HASH_MANAGER,
    Role::MODERATOR,
    Role::NEWS_MANAGER,
    Role::PLAY_TESTER,
    Role::QUALITY_ASSURANCE,
    Role::TICKET_MANAGER,
    Role::WRITER,
];

$modAssignable = [
    Role::DEV_COMPLIANCE,
    Role::QUALITY_ASSURANCE,
    Role::DEVELOPER,
    Role::DEVELOPER_JUNIOR,
    Role::ARTIST,
    Role::WRITER,
    Role::GAME_EDITOR,
    Role::PLAY_TESTER,
    Role::CHEAT_INVESTIGATOR,
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
        'name' => Role::DEV_COMPLIANCE,
        'display' => 4,
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::QUALITY_ASSURANCE,
        'display' => 4,
        'staff' => true,
        'legacy_role' => Permissions::Developer,
    ],
    [
        'name' => Role::CODE_REVIEWER,
        'display' => 4,
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
        'assign' => $modAssignable,
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
        'name' => Role::TEAM_ACCOUNT,
        'display' => 1,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::BETA,
        'display' => 0,
        'legacy_role' => Permissions::Registered,
    ],

    // vanity roles assigned by admin

    [
        'name' => Role::COMMUNITY_MANAGER,
        'display' => 3,
        'legacy_role' => Permissions::Registered,
    ],
    [
        'name' => Role::DEVELOPER_RETIRED,
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
