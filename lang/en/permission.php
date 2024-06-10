<?php

use App\Models\Role;

return [
    'role' => [
        Role::ROOT => 'Root',
        Role::ADMINISTRATOR => __('Administrator'),

        // moderation & platform staff roles

        Role::MODERATOR => __('Moderator'),
        Role::RELEASE_MANAGER => __('Release Manager'),
        Role::GAME_HASH_MANAGER => __('Hash Manager'),

        // creator roles

        Role::DEVELOPER_STAFF => __('Staff Developer'),
        Role::DEVELOPER => __('Developer'),
        Role::DEVELOPER_JUNIOR => __('Junior Developer'),
        Role::ARTIST => __('Artist'),
        Role::WRITER => __('Writer'),
        Role::GAME_EDITOR => __('Game Editor'),
        Role::PLAY_TESTER => __('Play Tester'),

        // community staff roles

        Role::FORUM_MANAGER => __('Forum Manager'),
        Role::TICKET_MANAGER => __('Ticket Manager'),
        Role::NEWS_MANAGER => __('News Manager'),
        Role::EVENT_MANAGER => __('Event Manager'),
        Role::CHEAT_INVESTIGATOR => __('Cheat Investigator'),

        // vanity roles assigned by root

        Role::FOUNDER => __('Founder'),
        Role::ARCHITECT => __('Architect'),
        Role::ENGINEER => __('Engineer'),
        Role::TEAM_ACCOUNT => __('Team Account'),
        Role::BETA => __('Beta'),

        // vanity roles assigned by admins

        Role::DEVELOPER_VETERAN => __('Veteran Developer'),
    ],
];
