<?php

declare(strict_types=1);

use App\Models\Role;

/**
 * TODO some teams may not even have roles (eg: DevQuest).
 * it may be desirable for teams to be a new resource that lives in Filament.
 * the resource could then have either roles or individual users attached to it.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Team Account Role Mappings
    |--------------------------------------------------------------------------
    |
    | This configuration defines which roles grant access to post on behalf
    | of specific team accounts. Team accounts are special user accounts that
    | represent organizational teams rather than individuals.
    |
    | Format: 'TeamAccountUsername' => [Role::ROLE_NAME, ...]
    |
    */

    'accounts' => [
        'CodeReviewTeam' => [Role::CODE_REVIEWER],
        'DevCompliance' => [Role::DEV_COMPLIANCE],
        'QATeam' => [Role::QUALITY_ASSURANCE],
        'RAArtTeam' => [Role::ARTIST],
        'RACheats' => [Role::CHEAT_INVESTIGATOR],
        'RAdmin' => [Role::ADMINISTRATOR, Role::MODERATOR],
        'RAEvents' => [Role::EVENT_MANAGER],
        'WritingTeam' => [Role::WRITER],
    ],
];
