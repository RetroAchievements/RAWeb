<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Models\Event;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ReorderSiteAwardsController extends Controller
{
    public function index()
    {
        $awards = $this->getUsersSiteAwards(request()->user());
        [$gameAwards, $eventAwards, $siteAwards, $eventData] = $this->SeparateAwards($awards);

        return Inertia::render('reorder-site-awards')
            ->with('gameAwards', $gameAwards)
            ->with('eventAwards', $eventAwards)
            ->with('siteAwards', $siteAwards)
            ->with('eventData', $eventData);
    }

    public function getUsersSiteAwards(?User $user): array
    {
        $dbResult = [];

        if (! $user) {
            return $dbResult;
        }

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

            $award['AwardType'] = AwardType::from($award['award_type'])->toLegacyInteger();
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

    public function SeparateAwards(array $userAwards): array
    {
        $awardEventGameIds = [];
        $awardEventIds = [];
        foreach ($userAwards as $award) {
            $type = (int) $award['AwardType'];
            if ($type === AwardType::Event->toLegacyInteger()) {
                $awardEventIds[] = (int) $award['AwardData'];
            } elseif (AwardType::isGame($type) && $award['ConsoleName'] === 'Events') {
                $awardEventGameIds[] = (int) $award['AwardData'];
            }
        }

        if (!empty($awardEventGameIds)) {
            $awardEventIds = array_merge($awardEventIds,
                Event::whereIn('legacy_game_id', $awardEventIds)->select('id')->pluck('id')->toArray()
            );
        }

        $eventData = new Collection();
        if (!empty($awardEventIds)) {
            $eventData = Event::whereIn('id', $awardEventIds)->with('legacyGame')->get()->keyBy('id');
        }

        $gameAwards = []; // Mastery awards that aren't Events.
        $eventAwards = []; // Event awards and Events mastery awards.
        $siteAwards = []; // Dev event awards and non-game active awards.

        foreach ($userAwards as $award) {
            $type = (int) $award['AwardType'];
            $id = (int) $award['AwardData'];

            if (AwardType::isGame($type)) {
                if ($award['ConsoleName'] === 'Events') {
                    $eventAwards[] = $award;
                } elseif ($type !== AwardType::GameBeaten->toLegacyInteger()) {
                    $gameAwards[] = $award;
                }
            } elseif ($type === AwardType::Event->toLegacyInteger()) {
                if ($eventData[$id]?->gives_site_award) {
                    $siteAwards[] = $award;
                } else {
                    $eventAwards[] = $award;
                }
            } elseif (AwardType::isActive($type)) {
                $siteAwards[] = $award;
            }
        }

        return [$gameAwards, $eventAwards, $siteAwards, $eventData];
    }

}
