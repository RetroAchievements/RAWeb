import type { FC } from 'react';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { RichPresenceMessage } from '@/common/components/RichPresenceMessage';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const GameRecentPlayersList: FC = () => {
  const { game, recentPlayers } = usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <ol className="zebra-list flex flex-col">
      {recentPlayers.map((recentPlayer) => (
        <li
          key={`mobile-recent-player-${recentPlayer.user.displayName}`}
          className="flex w-full flex-col gap-1 p-1.5 first:rounded-t-lg last:rounded-b-lg"
        >
          <div className="flex w-full items-center justify-between">
            <UserAvatar {...recentPlayer.user} size={20} />

            <div className="flex flex-col items-end gap-0.5">
              <DiffTimestamp
                at={recentPlayer.richPresenceUpdatedAt}
                className={cn(
                  'text-2xs',
                  recentPlayer.isActive ? 'text-green-500' : 'text-neutral-500',
                )}
              />

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
                className="!py-0"
              />
            </div>
          </div>

          <p className="truncate text-2xs">
            <RichPresenceMessage gameTitle={game.title} message={recentPlayer.richPresence} />
          </p>
        </li>
      ))}
    </ol>
  );
};
