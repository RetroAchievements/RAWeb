<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * This is distinct from the `Platform` entities we hold in the
 * DB. Those entities are associated with emulators. This enum
 * is used for pattern-matching against the user's browser
 * user agent so we can concretely trigger certain behaviors
 * conditionally based on whatever we detect.
 */
#[TypeScript]
enum UserPlatform: string
{
    case Android = 'Android';
    case IOS = 'iOS';
    case Linux = 'Linux';
    case MacOS = 'macOS';
    case Windows = 'Windows';
}
