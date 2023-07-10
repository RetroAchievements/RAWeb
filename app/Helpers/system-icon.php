<?php

declare(strict_types=1);

function getSystemIconUrl(int $consoleID): string
{
    $fallBackConsoleIcon = asset("assets/images/system/unknown.png");

    $name_short = config("systems.$consoleID.name_short");
    if (empty($name_short)) {
        return $fallBackConsoleIcon;
    }

    $cleanSystemShortName = Str::lower(str_replace("/", "", $name_short));
    $iconName = Str::kebab($cleanSystemShortName);
    $iconPath = public_path("assets/images/system/$iconName.png");
    $iconUrl = file_exists($iconPath) ? asset("assets/images/system/$iconName.png") : $fallBackConsoleIcon;

    return $iconUrl;
}
