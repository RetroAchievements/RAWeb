@props([
    'label' => '',
    'metadataValue' => '',
    'gameHubs' => [],
    'altLabels' => [],
])

<?php
// The metadata may contain several comma-separated values, so we need to split them up.
// This allows us to process each value separately and linkify any that match a game hub.
$metadataValues = !empty($metadataValue) ? array_map('trim', explode(',', $metadataValue)) : [];
$unmergedKeys = array_keys($metadataValues);

// If there are game hubs provided, we need to merge them with the metadata.
if ($gameHubs) {
    // We merge the main label and the alternative labels together to consider all possible metadata entries.
    foreach (array_merge([$label], $altLabels) as $hubCategory) {
        $hubPrefix = "[$hubCategory - ";

        foreach ($gameHubs as $hub) {
            $title = $hub['Title'];

            // We need to check if the game hub's title starts with the current label (or an alternative label).
            // If it does, we can linkify the corresponding metadata value.
            if (str_starts_with($title, $hubPrefix)) {
                $value = str_starts_with($hubCategory, "Hack")
                    ? str_replace("Hacks - ", "Hack - ", substr($title, 1, -1)) // For "Hack", normalize the title.
                    : substr($title, strlen($hubPrefix), -1); // Otherwise, just remove the prefix.

                $key = array_search($value, $metadataValues);
                if ($key !== false) {
                    unset($unmergedKeys[$key]);
                }

                // If the value is already in metadataValues, we replace it with a linkified version.
                // Otherwise, we add a new entry to the end of the array.
                $metadataValues[$key ?? array_push($metadataValues, '') - 1] = "<a href='/game/{$hub['gameIDAlt']}'>$value</a>";
            }
        }
    }
}

// We sanitize remaining unmerged values for output. This ensures that they are safe to display
// in the server-side rendered HTML. These are values that didn't match a hub, so they weren't
// linkified earlier.
if (!empty($metadataValues)) {
    foreach ($unmergedKeys as $key) {
        sanitize_outputs($metadataValues[$key]);
    }
}
?>

@if (!empty($metadataValues))
    <div class='flex'>
        <p class='tracking-tight w-[100px] min-w-[100px]'>{{ $label }}</p>
        <p class='font-semibold'>{!! implode(', ', $metadataValues) !!}</p>
    </div>
@endif