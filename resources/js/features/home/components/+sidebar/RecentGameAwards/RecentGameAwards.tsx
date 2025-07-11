import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { GameAvatar } from '@/common/components/GameAvatar';
import { SystemChip } from '@/common/components/SystemChip';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AvatarSize } from '@/common/models';

export const RecentGameAwards: FC = () => {
  const { mostRecentGameBeaten, mostRecentGameMastered } =
    usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-8 sm:grid sm:grid-cols-2 lg:flex">
      {mostRecentGameMastered ? (
        <div className="flex flex-col gap-1">
          <GameAwardHeadline>
            <p className="text-text">{t('Most recent set mastered')}</p>
            <p>
              <DiffTimestamp at={mostRecentGameMastered.awardedAt} />
            </p>
          </GameAwardHeadline>

          <GameAwardCard game={mostRecentGameMastered.game} user={mostRecentGameMastered.user} />
        </div>
      ) : null}

      {mostRecentGameBeaten ? (
        <div className="flex flex-col gap-1">
          <GameAwardHeadline>
            <p className="text-text">{t('Most recent game beaten')}</p>
            <p>
              <DiffTimestamp at={mostRecentGameBeaten.awardedAt} />
            </p>
          </GameAwardHeadline>

          <GameAwardCard game={mostRecentGameBeaten.game} user={mostRecentGameBeaten.user} />
        </div>
      ) : null}
    </div>
  );
};

interface GameAwardHeadlineProps {
  children: ReactNode;
}

const GameAwardHeadline: FC<GameAwardHeadlineProps> = ({ children }) => {
  return <div className="flex w-full justify-between text-2xs text-neutral-400/90">{children}</div>;
};

interface GameAwardCardProps {
  game: App.Platform.Data.Game;
  user: App.Data.User;
}

const GameAwardCard: FC<GameAwardCardProps> = ({ game, user }) => {
  const system = game.system as App.Platform.Data.System;

  return (
    <div className="flex h-20 gap-2.5 rounded bg-embed p-2">
      <GameAvatar {...game} size={64} showLabel={false} />

      <div className="flex flex-col gap-1">
        <div className="-mt-0.5 flex flex-col">
          <span className="text-xs">
            <GameAvatar {...game} showImage={false} gameTitleClassName="line-clamp-1" />
          </span>

          <SystemChip {...system} className="bg-zinc-800" />
        </div>

        <UserAvatar {...user} size={20 as AvatarSize} />
      </div>
    </div>
  );
};
