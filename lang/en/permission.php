<?php

use App\Site\Models\Role;

return [
    'role' => [
        Role::ROOT => 'Root',
        Role::ADMINISTRATOR => __('Administrator'),

        // moderation & platform staff roles

        Role::MODERATOR => __('Moderator'),
        Role::RELEASE_MANAGER => __('Release Manager'),
        Role::HUB_MANAGER => __('Hub Manager'),

        // creator roles

        Role::DEVELOPER_STAFF => __('Staff Developer'),
        Role::DEVELOPER => __('Developer'),
        Role::DEVELOPER_JUNIOR => __('Junior Developer'),
        Role::ARTIST => __('Artist'),
        Role::WRITER => __('Writer'),
        Role::TESTER => __('Tester'),

        // community staff roles

        Role::FORUM_MANAGER => __('Forum Manager'),
        Role::TICKET_MANAGER => __('Ticket Manager'),
        Role::NEWS_MANAGER => __('News Manager'),
        Role::EVENT_MANAGER => __('Event Manager'),

        // vanity roles assigned by root

        Role::FOUNDER => __('Founder'),
        Role::ARCHITECT => __('Architect'),
        Role::ENGINEER => __('Engineer'),
        Role::BETA => __('Beta'),

        // vanity roles assigned by admins

        Role::DEVELOPER_VETERAN => __('Veteran'),
    ],
];
