import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC, ReactNode } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';
import { SystemChip } from '@/common/components/SystemChip';
import { UserAvatar } from '@/common/components/UserAvatar';
import type { AvatarSize } from '@/common/models';

const mockGame: App.Platform.Data.Game = {
  id: 7528,
  title: 'Shanghai II',
  badgeUrl: 'http://media.retroachievements.org/Images/099406.png',
  system: {
    id: 7,
    name: 'NES/Famicom',
    iconUrl: 'http://localhost:64000/assets/images/system/nes.png',
    nameShort: 'NES',
  },
};

const mockGame2: App.Platform.Data.Game = {
  id: 14034,
  title: 'Number Munchers',
  badgeUrl: 'http://media.retroachievements.org/Images/073051.png',
  system: {
    id: 38,
    name: 'Apple II',
    iconUrl: 'http://localhost:64000/assets/images/system/a2.png',
    nameShort: 'A2',
  },
};

const mockUser: App.Data.User = {
  id: 1,
  displayName: 'Scott',
  avatarUrl: 'http://media.retroachievements.org/UserPic/Scott.png',
  isMuted: false,
  mutedUntil: null,
};

export const RecentGameAwards: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="flex flex-col gap-8 sm:grid sm:grid-cols-2 lg:flex">
      <div className="flex flex-col gap-1">
        <GameAwardHeadline>
          <p>{t('Most recent game mastered')}</p>
          <p>{'2 MINS AGO'}</p>
        </GameAwardHeadline>

        <GameAwardCard game={mockGame} user={mockUser} />
      </div>

      <div className="flex flex-col gap-1">
        <GameAwardHeadline>
          <p>{t('Most recent game beaten')}</p>
          <p>{'1 MIN AGO'}</p>
        </GameAwardHeadline>

        <GameAwardCard game={mockGame2} user={mockUser} />
      </div>
    </div>
  );
};

interface GameAwardHeadlineProps {
  children: ReactNode;
}

const GameAwardHeadline: FC<GameAwardHeadlineProps> = ({ children }) => {
  return (
    <div className="flex w-full justify-between text-2xs uppercase text-neutral-400/90">
      {children}
    </div>
  );
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
            <GameAvatar {...game} showImage={false} />
          </span>

          <SystemChip {...system} className="bg-zinc-800" />
        </div>

        <UserAvatar {...user} size={20 as AvatarSize} />
      </div>
    </div>
  );
};
