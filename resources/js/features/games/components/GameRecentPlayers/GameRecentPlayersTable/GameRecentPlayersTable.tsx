import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { RichPresenceMessage } from '@/common/components/RichPresenceMessage';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const GameRecentPlayersTable: FC = () => {
  const { game, recentPlayers } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  return (
    <BaseTable className="table-highlight hidden overflow-hidden rounded-lg bg-embed p-2 sm:table">
      <BaseTableHeader className="sr-only">
        <BaseTableRow>
          <BaseTableHead>{t('Player')}</BaseTableHead>
          <BaseTableHead>{t('Last Seen')}</BaseTableHead>
          <BaseTableHead>{t('Progress')}</BaseTableHead>
          <BaseTableHead>{t('Activity')}</BaseTableHead>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>
        {recentPlayers.map((recentPlayer) => (
          <BaseTableRow
            key={`desktop-recent-player-${recentPlayer.user.displayName}`}
            className="first:rounded-t-lg last:rounded-b-lg"
          >
            <BaseTableCell>
              <UserAvatar {...recentPlayer.user} size={24} />
            </BaseTableCell>

            <BaseTableCell>
              <DiffTimestamp
                at={recentPlayer.richPresenceUpdatedAt}
                className={cn(
                  'whitespace-nowrap',
                  recentPlayer.isActive ? 'text-green-500' : 'text-neutral-500',
                )}
                style="narrow"
              />
            </BaseTableCell>

            <BaseTableCell>
              <PlayerGameProgressBar
                game={game}
                playerGame={{
                  achievementsUnlocked: recentPlayer.achievementsUnlocked,
                  achievementsUnlockedHardcore: recentPlayer.achievementsUnlockedHardcore,
                  achievementsUnlockedSoftcore: recentPlayer.achievementsUnlockedSoftcore,
                  beatenAt: null,
                  beatenHardcoreAt: null,
                  completedAt: null,
                  completedHardcoreAt: null,
                  highestAward: recentPlayer.highestAward,
                  points: recentPlayer.points,
                  pointsHardcore: recentPlayer.pointsHardcore,
                }}
              />
            </BaseTableCell>

            <BaseTableCell>
              <span className="line-clamp-1" title={recentPlayer.richPresence}>
                <RichPresenceMessage gameTitle={game.title} message={recentPlayer.richPresence} />
              </span>
            </BaseTableCell>
          </BaseTableRow>
        ))}
      </BaseTableBody>
    </BaseTable>
  );
};
