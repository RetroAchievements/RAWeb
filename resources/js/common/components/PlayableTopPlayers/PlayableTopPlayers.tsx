import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';

import { InertiaLink } from '../InertiaLink';
import type { PlayerWithRank, TopPlayersListKind } from './models';
import { PlayableTopPlayersRow } from './PlayableTopPlayersRow';

interface PlayableTopPlayersProps {
  achievements: App.Platform.Data.Achievement[];
  game: App.Platform.Data.Game;
  numMasters: number;
  players: App.Platform.Data.GameTopAchiever[];
  variant: 'game' | 'event';
}

export const PlayableTopPlayers: FC<PlayableTopPlayersProps> = ({
  achievements,
  game,
  numMasters,
  players,
  variant,
}) => {
  const { t } = useTranslation();

  if (!players.length) {
    return null;
  }

  const areAllAchievementsOnePoint = !!achievements.every((a) => a?.points && a.points === 1);

  const listKind = getListKind(areAllAchievementsOnePoint, numMasters);
  const playersWithRanks = calculatePlayerRanks(players, listKind);

  return (
    <div data-testid="top-players">
      <h2 className="mb-0 border-0 text-lg font-semibold">
        {listKind === 'latest-masters' ? t('Latest Masters') : null}
        {listKind === 'most-achievements-earned' ? t('Most Achievements Earned') : null}
        {listKind === 'most-points-earned' ? t('Most Points Earned') : null}
      </h2>

      <div className="flex flex-col gap-2 rounded-lg bg-embed p-2 light:border light:border-neutral-200 light:bg-white">
        <BaseTable className="table-highlight overflow-hidden rounded-lg outline outline-1 outline-neutral-800 light:outline-white">
          <BaseTableHeader className="border-neutral-800">
            <BaseTableRow className="do-not-highlight text-menu-link">
              <BaseTableCell className="text-right">{'#'}</BaseTableCell>

              <BaseTableCell>{t('User')}</BaseTableCell>

              {listKind === 'latest-masters' ? (
                <BaseTableCell>{t('Mastered')}</BaseTableCell>
              ) : (
                <BaseTableCell className="text-right">
                  {areAllAchievementsOnePoint ? t('Achievements') : t('Points')}
                </BaseTableCell>
              )}
            </BaseTableRow>
          </BaseTableHeader>

          <BaseTableBody>
            {playersWithRanks.map((player) => (
              <PlayableTopPlayersRow
                key={`top-players-${player.userDisplayName}`}
                awardKind={
                  variant === 'game' ? calculateAwardKind(player, achievements.length) : null
                }
                calculatedRank={player.calculatedRank}
                listKind={listKind}
                numMasters={numMasters}
                player={player}
                playerIndex={player.rankIndex}
              />
            ))}
          </BaseTableBody>
        </BaseTable>

        {(game?.playersHardcore ?? 0) > 10 ? (
          <div className="flex w-full justify-end">
            <InertiaLink
              href={route('game.top-achievers.index', { game: game.id })}
              className="text-2xs"
              prefetch="desktop-hover-only"
            >
              {t('See more')}
            </InertiaLink>
          </div>
        ) : null}
      </div>
    </div>
  );
};

function calculateAwardKind(
  playerWithRank: PlayerWithRank,
  achievementsTotal: number,
): 'mastery' | 'beaten-hardcore' | null {
  if (playerWithRank.achievementsUnlockedHardcore === achievementsTotal) {
    return 'mastery';
  }

  if (playerWithRank.beatenHardcoreAt) {
    return 'beaten-hardcore';
  }

  return null;
}

/**
 * Calculate ranks for Most Points/Achievements Earned, handling ties appropriately.
 */
function calculatePlayerRanks(
  players: App.Platform.Data.GameTopAchiever[],
  listKind: TopPlayersListKind,
): PlayerWithRank[] {
  // First, add the index to each player for referencing.
  const playersWithRanks: PlayerWithRank[] = players.map((player, index) => {
    return { ...player, rankIndex: index };
  });

  // Only calculate ranks for non-"latest-masters" lists.
  if (listKind !== 'latest-masters') {
    let currentRank = 1;
    let nextRank = 1;

    // Calculate ranks with ties.
    for (let i = 0; i < playersWithRanks.length; i += 1) {
      const player = playersWithRanks[i];

      // This is either the first player or has a different score from previous player.
      if (i === 0 || player.pointsHardcore !== playersWithRanks[i - 1].pointsHardcore) {
        currentRank = nextRank;
      }

      player.calculatedRank = currentRank;
      nextRank = i + 2;
    }
  }

  return playersWithRanks;
}

function getListKind(areAllAchievementsOnePoint: boolean, numMasters: number): TopPlayersListKind {
  if (numMasters > 10) {
    return 'latest-masters';
  }

  if (areAllAchievementsOnePoint) {
    return 'most-achievements-earned';
  }

  return 'most-points-earned';
}
