<?php

declare(strict_types=1);

use App\Models\System;

function getSystemIconUrl(int|string|System $system): string
{
    $fallBackConsoleIcon = asset("assets/images/system/unknown.png");

    if (is_string($system)) {
        $shortName = $system;
    } else {
        if (is_int($system)) {
            if ($system < 1) {
                return $fallBackConsoleIcon;
            }

            $system = System::find($system);
            if (!$system) {
                return $fallBackConsoleIcon;
            }
        }
        $shortName = $system->name_short ?? '';
    }

    $cleanSystemShortName = Str::lower(str_replace("/", "", $shortName));
    $iconName = Str::kebab($cleanSystemShortName);
    $iconPath = public_path("assets/images/system/$iconName.png");
    $iconUrl = file_exists($iconPath) ? asset("assets/images/system/$iconName.png") : $fallBackConsoleIcon;

    return $iconUrl;
}
