<?php

function RenderUserPref(
    int $websitePrefs,
    int $userPref,
    bool $setIfTrue,
    ?string $state = null,
    int $targetLoadingIcon = 1,
): void {
    echo "<input id='UserPreference$userPref' type='checkbox' ";
    echo "onchange='DoChangeUserPrefs($targetLoadingIcon); return false;' value='1'";

    if ($state) {
        echo " $state";
    } elseif (BitSet($websitePrefs, $userPref) === $setIfTrue) {
        echo " checked";
    }

    echo " />";
}
