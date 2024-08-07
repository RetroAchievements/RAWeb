<?php

declare(strict_types=1);

use App\Models\System;

function getSystemIconUrl(int|System $system): string
{
    $fallBackConsoleIcon = asset("assets/images/system/unknown.png");

    if (is_int($system)) {
        if ($system < 1) {
            return $fallBackConsoleIcon;
        }

        $system = System::find($system);
        if (!$system) {
            return $fallBackConsoleIcon;
        }
    }

    $cleanSystemShortName = Str::lower(str_replace("/", "", $system->name_short ?? ''));
    $iconName = Str::kebab($cleanSystemShortName);
    $iconPath = public_path("assets/images/system/$iconName.png");
    $iconUrl = file_exists($iconPath) ? asset("assets/images/system/$iconName.png") : $fallBackConsoleIcon;

    return $iconUrl;
}
