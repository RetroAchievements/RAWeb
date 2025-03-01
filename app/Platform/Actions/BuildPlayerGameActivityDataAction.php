<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameHashData;
use App\Platform\Data\ParsedUserAgentData;
use App\Platform\Data\PlayerGameActivityData;
use App\Platform\Data\PlayerGameActivityEventData;
use App\Platform\Data\PlayerGameActivitySessionData;
use App\Platform\Data\PlayerGameActivitySummaryData;
use App\Platform\Data\PlayerGameClientBreakdownData;
use App\Platform\Services\PlayerGameActivityService;
use App\Platform\Services\UserAgentService;

class BuildPlayerGameActivityDataAction
{
    public function __construct(
        protected PlayerGameActivityService $playerGameActivityService,
        protected UserAgentService $userAgentService,
    ) {
    }

    public function execute(User $user, Game $game): PlayerGameActivityData
    {
        $this->playerGameActivityService->initialize($user, $game);
        $summary = $this->playerGameActivityService->summarize();

        /**
         * @var array<int, array{
         *     events: array<int, array{
         *         achievement?: array{
         *             ID: int,
         *             Title: string,
         *             Description: string,
         *             Points: int,
         *             TrueRatio: float,
         *             BadgeName: string,
         *             Flags: int
         *         }
         *     }>,
         *     userAgent?: string,
         *     playerSession?: array{gameHash?: mixed}
         * }> $sessions
         */
        $sessions = $this->playerGameActivityService->sessions;

        $mappedSessions = array_map(function (array $session): PlayerGameActivitySessionData {
            /** @var array<int, array{achievement?: array}> $events */
            $events = $session['events'];

            $mappedEvents = array_map(function (array $event): PlayerGameActivityEventData {
                if (isset($event['achievement'])) {
                    // Create a temporary Achievement model for the transformation.
                    $achievement = (new Achievement())->forceFill([
                        'ID' => $event['achievement']['ID'],
                        'Title' => $event['achievement']['Title'],
                        'Description' => $event['achievement']['Description'],
                        'Points' => $event['achievement']['Points'],
                        'TrueRatio' => $event['achievement']['Points'],
                        'BadgeName' => $event['achievement']['BadgeName'],
                        'Flags' => $event['achievement']['Flags'],
                    ]);

                    $event['achievement'] = AchievementData::fromAchievement($achievement)->include(
                        'flags',
                        'points',
                    );
                }

                return PlayerGameActivityEventData::from($event);
            }, $events);

            $parsedUserAgent = isset($session['userAgent']) && is_string($session['userAgent'])
                ? ParsedUserAgentData::from($this->userAgentService->decode($session['userAgent']))
                : null;

            $gameHash = isset($session['playerSession']['gameHash'])
                ? GameHashData::fromGameHash($session['playerSession']['gameHash'])
                    ->include('isMultiDisc')
                : null;

            return PlayerGameActivitySessionData::from([
                ...$session,
                'events' => $mappedEvents,
                'parsedUserAgent' => $parsedUserAgent,
                'gameHash' => $gameHash,
            ]);
        }, $sessions);

        /** @var array<string, array{clientIdentifier: string}> $clientBreakdownData */
        $clientBreakdownData = $this->playerGameActivityService->getClientBreakdown($this->userAgentService);

        $clientBreakdown = array_map(
            fn (array $client, string $identifier): PlayerGameClientBreakdownData => PlayerGameClientBreakdownData::from([
                ...$client,
                'clientIdentifier' => $identifier,
            ]),
            $clientBreakdownData,
            array_keys($clientBreakdownData)
        );

        return new PlayerGameActivityData(
            sessions: $mappedSessions,
            clientBreakdown: array_values($clientBreakdown),
            summarizedActivity: new PlayerGameActivitySummaryData(...$summary),
        );
    }
}
