<?php

use App\Site\Models\Role;

return [
    'role' => [
        Role::ROOT => 'Root',
        Role::STAFF => __('Staff'),
        Role::ADMINISTRATOR => __('Administrator'),
        Role::MODERATOR => __('Moderator'),
        Role::BETA => __('Beta'),

        Role::DEVELOPER => __('Development Access'),
        Role::DEVELOPER_LEVEL_1 => __('Sr. Developer'),
        Role::DEVELOPER_LEVEL_2 => __('Developer'),
        Role::DEVELOPER_LEVEL_3 => __('Jr. Developer'),
        Role::DEVELOPER_VETERAN => __('Veteran'),

        Role::ARTIST => __('Artist'),

        Role::FORUM_MANAGER => __('Forum Manager'),
        Role::HUB_MANAGER => __('Hub Manager'),
        Role::TICKET_MANAGER => __('Ticket Manager'),
        Role::RELEASE_MANAGER => __('Release Manager'),

        Role::NEWS_MANAGER => __('News Manager'),
        // Role::NEWS_EDITOR => __('News Editor'),

        Role::EVENT_MANAGER => __('Event Manager'),

        Role::ARCHITECT => __('Architect'),
        Role::ENGINEER => __('Engineer'),
        Role::FOUNDER => __('Founder'),
    ],
];
