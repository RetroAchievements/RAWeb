import type { FC, ReactNode } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';

interface GameHeadingProps {
  children: ReactNode;
  game: App.Platform.Data.Game;
}

export const GameHeading: FC<GameHeadingProps> = ({ children, game }) => {
  return (
    <div className="mb-3 flex w-full gap-x-3">
      <div className="mb-2 inline self-end">
        <GameAvatar {...game} showLabel={false} size={48} />
      </div>

      <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">{children}</h1>
    </div>
  );
};
