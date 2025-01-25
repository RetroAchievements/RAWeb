<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\RoleData;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserSettingsPageProps')]
class UserSettingsPagePropsData extends Data
{
    public function __construct(
        public UserData $userSettings,
        public UserPermissionsData $can,
        public ?string $requestedUsername = null,
        /** @var RoleData[] */
        public array $displayableRoles,
    ) {
    }
}
