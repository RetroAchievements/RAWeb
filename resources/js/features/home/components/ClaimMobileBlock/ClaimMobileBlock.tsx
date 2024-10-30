import type { FC } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';
import { GameTitle } from '@/common/components/GameTitle';
import { UserAvatar } from '@/common/components/UserAvatar';
import type { AvatarSize } from '@/common/models';

interface ClaimMobileBlockProps {
  game: App.Platform.Data.Game;
  user: App.Data.User;
}

export const ClaimMobileBlock: FC<ClaimMobileBlockProps> = ({ game, user }) => {
  return (
    <div className="w-full rounded bg-embed p-2">
      <div className="flex items-center gap-x-2.5">
        <GameAvatar {...game} showLabel={false} size={48} />

        <div className="flex w-full flex-col gap-y-0.5">
          <a href={route('game.show', { game: game.id })} className="cursor-pointer leading-4">
            <GameTitle title={game.title} />
          </a>

          {game.system ? (
            <div
              data-testid="claim-system"
              className="flex w-full justify-between text-xs tracking-tighter"
            >
              <span>{game.system.name}</span>
            </div>
          ) : null}

          <div className="flex justify-between text-xs">
            <UserAvatar {...user} size={14 as AvatarSize} />
            <span className="text-xs tracking-tighter">{'New, 2 weeks ago'}</span>
          </div>
        </div>
      </div>
    </div>
  );
};
