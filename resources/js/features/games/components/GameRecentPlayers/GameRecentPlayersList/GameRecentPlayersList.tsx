import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { RichPresenceMessage } from '@/common/components/RichPresenceMessage';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface GameRecentPlayersListProps {
  canToggleExpanded: boolean;
  isExpanded: boolean;
  onToggleExpanded: () => void;
}

export const GameRecentPlayersList: FC<GameRecentPlayersListProps> = ({
  canToggleExpanded,
  isExpanded,
  onToggleExpanded,
}) => {
  const { backingGame, recentPlayers } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  return (
    <ol className="zebra-list flex flex-col">
      {recentPlayers.map((recentPlayer) => {
        const playerKey = `mobile-recent-player-${recentPlayer.user.displayName}`;

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
                  game={backingGame}
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
                  shouldAlwaysLink={true}
                  href={route('game.compare-unlocks', {
                    game: backingGame.id,
                    user: recentPlayer.user.displayName,
                  })}
                  className="!py-0"
                  variant="minimal"
                />
              </div>
            </div>

            <button
              type="button"
              className={cn(
                'text-left text-2xs',
                'rounded focus:outline-none focus:ring-1 focus:ring-text focus:ring-offset-0',

                canToggleExpanded ? 'cursor-pointer' : null,
                !isExpanded ? 'truncate' : null,
              )}
              onClick={onToggleExpanded}
              aria-expanded={isExpanded}
              aria-label={t('Toggle rich presence details')}
            >
              <RichPresenceMessage
                gameTitle={backingGame.title}
                message={recentPlayer.richPresence}
              />
            </button>
          </li>
        );
      })}
    </ol>
  );
};
