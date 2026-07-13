<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\UserSettingsPageTab;
use App\Data\ConnectedOAuthApplicationData;
use App\Data\OAuthClientData;
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
        /** @var RoleData[] */
        public array $displayableRoles,
        public int $oauthApplicationLimit,
        public ?string $requestedUsername = null,
        public UserSettingsPageTab $initialTab = UserSettingsPageTab::Profile,
        /** @var OAuthClientData[] */
        public array $oauthApplications = [],
        /** @var ConnectedOAuthApplicationData[] */
        public array $connectedOAuthApplications = [],
    ) {
    }
}
