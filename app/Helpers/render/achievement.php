<?php

use App\Platform\Models\Achievement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

function achievementAvatar(
    int|string|array|Achievement $achievement,
    ?bool $label = null,
    bool|int|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool|string|array $tooltip = true,
    ?string $context = null,
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
            $label = str_replace("\n", '', Blade::render('<x-achievement.title :rawTitle="$rawTitle" />', ['rawTitle' => $label]));
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
        tooltip: is_array($tooltip)
            ? renderAchievementCard($tooltip, iconUrl: $icon)
            : $tooltip,
        iconUrl: $icon !== false && ($icon || !$label) ? $icon : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
        sanitize: $title === null,
        altText: $title ?? (is_string($label) ? $label : null),
    );
}

function renderAchievementCard(int|string|array $achievement, ?string $context = null, ?string $iconUrl = null): string
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
        $data = Cache::store('array')->rememberForever('achievement:' . $id . ':card-data', fn () => GetAchievementData($id));
    }

    $title = str_replace("\n", '', Blade::render('<x-achievement.title :rawTitle="$rawTitle" />', [
        'rawTitle' => $data['AchievementTitle'] ?? $data['Title'] ?? '',
    ]));
    $description = $data['AchievementDesc'] ?? $data['Description'] ?? null;
    $achPoints = $data['Points'] ?? null;
    $badgeName = $data['BadgeName'] ?? null;
    $type = $data['Type'] ?? $data['type'] ?? null;
    $badgeImgSrc = $iconUrl ?? media_asset("Badge/{$badgeName}.png");
    $renderedGameTitle = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $data['GameTitle'] ?? '']);
    $sanitizedGameTitle = str_replace("\n", '', $renderedGameTitle);
    $sanitizedGameTitle = trim(html_entity_decode($sanitizedGameTitle, ENT_QUOTES, 'UTF-8'));

    $renderedType = null;
    if ($type) {
        $renderedType = str_replace("\n", '', Blade::render(
            '<x-achievement.thin-type-indicator :type="$type" />',
            ['type' => $type]
        ));
    }

    $pointsLabel = $achPoints . " " . mb_strtolower(__res('point', (int) $achPoints));

    $unlockedHtml = null;
    if (isset($data['DateAwarded'])) {
        $unlockDate = Carbon::parse($data['DateAwarded'])->format('M j Y, g:ia');
        $unlockedHtml = "<div class='smalldate'>Unlocked " . $unlockDate;

        if (!$data['HardcoreAchieved']) {
            $unlockedHtml .= " (softcore)";
        }

        $unlockedHtml .= "</div>";
    }

    $tooltip = <<<HTML
        <div class="tooltip-body flex items-start gap-x-3 p-2 max-w-[400px] w-[400px]">
            <img src="$badgeImgSrc" width="64" height="64" />

            <div class="flex flex-col w-full gap-y-2">
                <div class="flex justify-between gap-1 w-full -mb-1">
                    <p class="font-bold mb-1 text-lg leading-5 [word-break:break-word]">$title</p>
                    <div class="-mt-0.5">$renderedType</div>
                </div>

                <p class="mb-1">$description</p>

                <div class="text-xs">
                    <p class="text-xs">$pointsLabel</p>
                    <p class="text-xs italic">$sanitizedGameTitle</p>
                </div>

                $unlockedHtml
            </div>
        </div>
    HTML;

    return trim(str_replace("\n", '', $tooltip));
}
