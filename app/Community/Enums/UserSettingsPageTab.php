<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserSettingsPageTab: string
{
    case Profile = 'profile';
    case Notifications = 'notifications';
    case Account = 'account';
    case Applications = 'applications';
}
