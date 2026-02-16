<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UserAwardData;
use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ReorderSiteAwardsController extends Controller
{
    public function index()
    {
        $awards = $this->getUsersSiteAwards(request()->user());
        $cleanAwards = $this->SeparateAwards($awards);

        return Inertia::render('reorder-site-awards')
            ->with('awards', $cleanAwards);
    }

    public function getUsersSiteAwards(User $user): array
    {
        $bindings = [
            'userId' => $user->id,
            'userId2' => $user->id,
            'userId3' => $user->id,
        ];

        $gameAwardValues = implode("','", AwardType::gameValues());

        $query = "
        -- game awards (mastery, beaten)
        SELECT " . unixTimestampStatement('saw.awarded_at', 'AwardedAt') . ", saw.award_type, saw.user_id, saw.award_key, saw.award_tier, saw.order_column, gd.title AS Title, s.id AS ConsoleID, s.name AS ConsoleName, NULL AS Flags, gd.image_icon_asset_path AS ImageIcon
            FROM user_awards AS saw
            LEFT JOIN games AS gd ON ( gd.id = saw.award_key AND saw.award_type IN ('{$gameAwardValues}') )
            LEFT JOIN systems AS s ON s.id = gd.system_id
            WHERE
                saw.award_type IN('{$gameAwardValues}')
                AND saw.user_id = :userId
            GROUP BY saw.award_type, saw.award_key, saw.award_tier
            HAVING
                -- Remove duplicate game beaten awards.
                (saw.award_type != '" . AwardType::GameBeaten->value . "' OR saw.award_tier = 1 OR NOT EXISTS (
                    SELECT 1 FROM user_awards AS saw2
                    WHERE saw2.award_type = saw.award_type AND saw2.award_key = saw.award_key AND saw2.award_tier = 1 AND saw2.user_id = saw.user_id
                ))
                -- Remove duplicate mastery awards.
                AND (saw.award_type != '" . AwardType::Mastery->value . "' OR saw.award_tier = 1 OR NOT EXISTS (
                    SELECT 1 FROM user_awards AS saw3
                    WHERE saw3.award_type = saw.award_type AND saw3.award_key = saw.award_key AND saw3.award_tier = 1 AND saw3.user_id = saw.user_id
                ))
        UNION
        -- event awards
        SELECT " . unixTimestampStatement('saw.awarded_at', 'AwardedAt') . ", saw.award_type, saw.user_id, saw.award_key, saw.award_tier, saw.order_column, gd.title AS Title, " . System::Events . ", 'Events', NULL, e.image_asset_path AS ImageIcon
            FROM user_awards AS saw
            LEFT JOIN events e ON e.id = saw.award_key
            LEFT JOIN games gd ON gd.id = e.legacy_game_id
            WHERE
                saw.award_type = '" . AwardType::Event->value . "'
                AND saw.user_id = :userId3
        UNION
        -- non-game awards (developer contribution, ...)
        SELECT " . unixTimestampStatement('MAX(saw.awarded_at)', 'AwardedAt') . ", saw.award_type, saw.user_id, MAX( saw.award_key ), saw.award_tier, saw.order_column, NULL, NULL, NULL, NULL, NULL
            FROM user_awards AS saw
            WHERE
                saw.award_type NOT IN('{$gameAwardValues}','" . AwardType::Event->value . "')
                AND saw.user_id = :userId2
            GROUP BY saw.award_type
        ORDER BY order_column, AwardedAt, award_type, award_tier ASC";

        // TODO: Don't use legacy
        $dbResult = legacyDbFetchAll($query, $bindings)->toArray();

        foreach ($dbResult as &$award) {
            unset($award['user_id']);

            $award['AwardType'] = AwardType::from($award['award_type']);
            $award['AwardData'] = (int) $award['award_key'];
            $award['AwardDataExtra'] = (int) $award['award_tier'];
            $award['DisplayOrder'] = (int) $award['order_column'];

            if ($award['ConsoleID']) {
                $award['ConsoleID'] = (int) $award['ConsoleID'];
            }

            unset($award['award_type'], $award['award_key'], $award['award_tier'], $award['order_column']);
        }

        return $dbResult;
    }

    /*
     * array of ["AwardType" => enum AwardType, AwardData => int, AwardDataExtra => int, DisplayOrder => int, ConsoleID => int?]
     */
    /**
     * Parses awards into a usable state for the frontend.
     *
     * @param array $userAwards array of awards from the database.
     * @return array<UserAwardData>
     */
    public function SeparateAwards(array $userAwards): array
    {
        $awardEventGameIds = [];
        $awardEventIds = [];
        foreach ($userAwards as $award) {
            $type = $award['AwardType'];
            if ($type === AwardType::Event) {
                $awardEventIds[] = (int) $award['AwardData'];
            } elseif ($award['ConsoleName'] === 'Events' && AwardType::isGame($type)) {
                $awardEventGameIds[] = (int) $award['AwardData'];
            }
        }

        if (! empty($awardEventGameIds)) {
            $awardEventIds = array_merge($awardEventIds,
                Event::whereIn('legacy_game_id', $awardEventIds)->select('id')->pluck('id')->toArray()
            );
        }

        $eventData = new Collection;
        if (! empty($awardEventIds)) {
            $eventData = Event::whereIn('id', $awardEventIds)->with('legacyGame')->get()->keyBy('id');
        }

        $gameAwards = []; // Mastery awards that aren't Events.
        $eventAwards = []; // Event awards and Events mastery awards.
        $siteAwards = []; // Dev event awards and non-game active awards.

        /** @var UserAwardData[] $awards */
        $awards = [];

        foreach ($userAwards as $award) {
            $type = $award['AwardType'];
            $id = (int) $award['AwardData'];
            $extra = (int) $award['AwardDataExtra'];
            $awardDate = $award['AwardedAt'];

            $section = 'unknown';

            if (AwardType::isGame($type)) {
                if ($award['ConsoleName'] === 'Events') {
                    $section = 'event';
                } elseif ($type !== AwardType::GameBeaten) {
                    $section = 'game';
                    $award["ImageIcon"] = asset($award['ImageIcon']);
                    $gameId = $id;

                    $award["IsGold"] = $extra === 1;
                }
            } elseif ($type === AwardType::Event) {
                if ($eventData[$id]?->gives_site_award) {
                    $section = 'site';
                } else {
                    $section = 'event';
                }

                $event = $eventData->find($id);
                if ($event) {
                    $tooltip = "Awarded for completing the $event->title event";
                    $image = $event->image_asset_path;

                    if ($extra !== 0) {
                        $eventAward = EventAward::where('event_id', $id)
                            ->where('tier_index', $extra)
                            ->first();

                        if ($eventAward) {
                            $image = $eventAward->image_asset_path;

                            if ($eventAward->points_required < $event->legacyGame->points_total) {
                                $tooltip = "Awarded for earning at least $eventAward->points_required points in the $event->title event";
                            }
                        }
                    }

                    $award["Tooltip"] = $tooltip;
                    $award["ImageIcon"] = media_asset($image);
                    $award["IsGold"] = true;
                    $award["Link"] = route('event.show', $event->id);
                    /* <div class='p-2 max-w-[320px] text-pretty'><span>$tooltip</span><p class='italic'>{$awardDate}</p></div> */
                }

            } elseif (AwardType::isActive($type)) {
                $this->makeTooltip($award);
                $section = 'site';
            }

            if ($section === 'unknown') {
                continue;
            }

            $newAward = new UserAwardData(
                imageUrl: $award['ImageIcon'] ?? '',
                tooltip: $award['Tooltip'] ?? '',
                link: $award['Link'] ?? '',
                isGold: $award['IsGold'] ?? false,
                gameId: $gameId ?? null,
                dateAwarded: $award['AwardedAt'] . "",
                awardType: $award['AwardType'],
                awardSection: $section,
                displayOrder: $award['DisplayOrder'],
            );

            $awards[] = $newAward;
        }

        return $awards;
    }

    public function makeTooltip(&$award): void
    {
        switch ($award['AwardType']) {
            case AwardType::GameBeaten:
            case AwardType::Mastery:
                // no tooltip needed, frontend uses GameAvatar
                $award['Tooltip'] = null;

                return;
            case AwardType::AchievementPointsYield:
                $data = $award["AwardData"];
                $points = PlayerBadge::getBadgeThreshold(AwardType::AchievementPointsYield, $data);
                $award['Tooltip'] = "Awarded for producing many valuable achievements, providing over $points points to the community!";
                $award["ImageIcon"] = asset("/assets/images/badge/contribPoints-$data.png");
                $award["IsGold"] = true;

                return;
            case AwardType::AchievementUnlocksYield:
                $data = $award["AwardData"];
                $points = PlayerBadge::getBadgeThreshold(AwardType::AchievementUnlocksYield, $data);
                $award['Tooltip'] = "Awarded for being a hard-working developer and producing achievements that have been earned over $points times!";
                $award["ImageIcon"] = asset("/assets/images/badge/contribYield-$data.png");
                $award["IsGold"] = true;

                return;

            case AwardType::CertifiedLegend:
                $award['Tooltip'] = 'Specially Awarded to a Certified RetroAchievements Legend';
                $award["ImageIcon"] = asset('/assets/images/badge/legend.png');
                $award["IsGold"] = true;

                return;
            case AwardType::PatreonSupporter:
                $award['Tooltip'] = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
                $award["ImageIcon"] = asset('/assets/images/badge/patreon.png');
                $award["IsGold"] = true;
                $award["Link"] = route('patreon-supporter.index');

                return;
            case AwardType::Event:
                $award['Tooltip'] = 'Event Award';

                return;
        }
    }
}
