import type { FC } from 'react';
import { useState } from 'react';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { RichPresenceMessage } from '@/common/components/RichPresenceMessage';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const GameRecentPlayersList: FC = () => {
  const { game, recentPlayers } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [expandedItems, setExpandedItems] = useState<Set<string>>(new Set());

  const toggleExpanded = (playerKey: string) => {
    setExpandedItems((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(playerKey)) {
        newSet.delete(playerKey);
      } else {
        newSet.add(playerKey);
      }

      return newSet;
    });
  };

  return (
    <ol className="zebra-list flex flex-col">
      {recentPlayers.map((recentPlayer) => {
        const playerKey = `mobile-recent-player-${recentPlayer.user.displayName}`;
        const isExpanded = expandedItems.has(playerKey);

        return (
          <li
            key={playerKey}
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

            <button
              type="button"
              className={cn(
                'cursor-pointer text-left text-2xs',
                'rounded focus:outline-none focus:ring-1 focus:ring-text focus:ring-offset-0',

                !isExpanded ? 'truncate' : null,
              )}
              onClick={() => toggleExpanded(playerKey)}
              aria-expanded={isExpanded}
              aria-label={`Toggle rich presence details for ${recentPlayer.user.displayName}`}
            >
              <RichPresenceMessage gameTitle={game.title} message={recentPlayer.richPresence} />
            </button>
          </li>
        );
      })}
    </ol>
  );
};
