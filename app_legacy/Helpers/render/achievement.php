<?php

use Illuminate\Support\Facades\Cache;
use LegacyApp\Platform\Models\Achievement;

function achievementAvatar(
    int|string|array|Achievement $achievement,
    ?bool $label = null,
    bool|int|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool|string|array $tooltip = true,
    ?string $context = null,
    ?string $unlockDate = null,
): string {
    $id = $achievement;
    $title = null;

    if ($achievement instanceof Achievement) {
        $achievement = $achievement->toArray();
    }

    if (is_array($achievement)) {
        $id = $achievement['AchievementID'] ?? $achievement['ID'];

        if ($label !== false) {
            $title = $achievement['AchievementTitle'] ?? $achievement['Title'];
            $points = $achievement['Points'] ?? null;
            $label = $title . ($points ? ' (' . $points . ')' : '');
            sanitize_outputs($label);   // sanitize before rendering HTML
            $label = renderAchievementTitle($label);
        }

        if ($icon !== false) {
            $badgeName = is_string($icon) ? $icon : $achievement['BadgeName'] ?? null;
            $icon = media_asset("/Badge/$badgeName.png");
        }

        if ($achievement['HardcoreMode'] ?? false) {
            $iconClass = 'goldimage';
        }

        // pre-render tooltip
        $tooltip = $tooltip !== false ? $achievement : false;
    }

    return avatar(
        resource: 'achievement',
        id: $id,
        label: $label !== false && ($label || !$icon) ? $label : null,
        link: route('achievement.show', $id),
        tooltip: is_array($tooltip) ? renderAchievementCard($tooltip, unlockDate: $unlockDate) : $tooltip,
        iconUrl: $icon !== false && ($icon || !$label) ? $icon : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
        sanitize: $title === null,
        altText: $title ?? $label,
    );
}

/**
 * Render achievement title, parsing `[m]` (missable) as a tag
 */
function renderAchievementTitle(string $title, bool $tags = true): string
{
    if (!str_contains($title, '[m]')) {
        return $title;
    }
    $span = '';
    if ($tags) {
        $span = "<span class='tag missable' title='Missable'>"
            . "<abbr>[<b>m</b>]</abbr>"
            . "</span>";
    }

    return trim(str_replace('[m]', $span, $title));
}

function renderAchievementCard(int|string|array $achievement, ?string $context = null, ?string $unlockDate = null): string
{
    $id = is_int($achievement) || is_string($achievement) ? (int) $achievement : ($achievement['AchievementID'] ?? $achievement['ID'] ?? null);

    if (empty($id)) {
        return __('legacy.error.error');
    }

    $data = [];
    if (is_array($achievement)) {
        $data = $achievement;
    }

    if (empty($data)) {
        $data = Cache::store('array')->rememberForever('achievement:' . $id . ':card-data', function () use ($id) {
            $data = [];
            getAchievementMetadata($id, $data);

            return $data;
        });
    }

    $title = renderAchievementTitle($data['AchievementTitle'] ?? $data['Title'] ?? null);
    $description = $data['AchievementDesc'] ?? $data['Description'] ?? null;
    $achPoints = $data['Points'] ?? null;
    $badgeName = $data['BadgeName'] ?? null;
    $gameTitle = renderGameTitle($data['GameTitle'] ?? null);

    $tooltip = "<div class='tooltip-body flex items-start gap-2 p-2' style='max-width: 400px'>";
    $tooltip .= "<img src='" . media_asset("Badge/$badgeName.png") . "' width='64' height='64' />";
    $tooltip .= "<div>";
    $tooltip .= "<div><b>$title</b></div>";
    $tooltip .= "<div class='mb-1'>$description</div>";
    if ($achPoints) {
        $tooltip .= "<div>$achPoints Points</div>";
    }
    if ($gameTitle) {
        $tooltip .= "<div><i>$gameTitle</i></div>";
    }

    if ($unlockDate) {
        $unlockedStr = "<br clear=all>Unlocked on $unlockDate";
        if (!array_key_exists('HardcoreMode', $achievement) or $achievement['HardcoreMode'] === 1) {
            $unlockedStr .= "<br>HARDCORE";
        }
        $tooltip .= "<div>$unlockedStr</div>";
    }

    $tooltip .= "</div>";
    $tooltip .= "</div>";

    return $tooltip;
}
