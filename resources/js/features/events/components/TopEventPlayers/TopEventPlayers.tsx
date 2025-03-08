import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';

import type { TopPlayersListKind } from './top-players-list-kind.model';
import { TopEventPlayersRow } from './TopEventPlayersRow';

interface TopEventParticipantsProps {
  event: App.Platform.Data.Event;
  numMasters: number;
  players: App.Platform.Data.GameTopAchiever[];
}

export const TopEventPlayers: FC<TopEventParticipantsProps> = ({ event, numMasters, players }) => {
  const { t } = useTranslation();

  if (!players.length) {
    return null;
  }

  const areAllAchievementsOnePoint = !!event.eventAchievements?.every(
    (ea) => ea.achievement?.points && ea.achievement.points === 1,
  );

  const listKind = getListKind(areAllAchievementsOnePoint, numMasters);

  return (
    <div data-testid="top-players">
      <h2 className="mb-0 border-0 text-lg font-semibold">
        {listKind === 'latest-masters' ? t('Latest Masters') : null}
        {listKind === 'most-achievements-earned' ? t('Most Achievements Earned') : null}
        {listKind === 'most-points-earned' ? t('Most Points Earned') : null}
      </h2>

      <div className="flex flex-col gap-2 rounded-lg bg-embed p-2">
        <BaseTable className="table-highlight overflow-hidden rounded-lg outline outline-1 outline-neutral-800">
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
            {players.map((player, playerIndex) => (
              <TopEventPlayersRow
                key={`top-players-${player.userId}`}
                listKind={listKind}
                numMasters={numMasters}
                player={player}
                playerIndex={playerIndex}
              />
            ))}
          </BaseTableBody>
        </BaseTable>

        {(event.legacyGame?.playersHardcore ?? 0) > 10 ? (
          <div className="flex w-full justify-end">
            <a
              href={route('game.top-achievers.index', { game: event.legacyGame!.id })}
              className="text-2xs"
            >
              {t('See more')}
            </a>
          </div>
        ) : null}
      </div>
    </div>
  );
};

function getListKind(areAllAchievementsOnePoint: boolean, numMasters: number): TopPlayersListKind {
  if (numMasters > 10) {
    return 'latest-masters';
  }

  if (areAllAchievementsOnePoint) {
    return 'most-achievements-earned';
  }

  return 'most-points-earned';
}
