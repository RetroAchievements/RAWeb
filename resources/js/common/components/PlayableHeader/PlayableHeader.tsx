import type { FC, ReactNode } from 'react';

import { GameTitle } from '../GameTitle';

interface PlayableHeaderProps {
  badgeUrl: string;
  systemIconUrl: string;
  systemLabel: string;
  title: string;

  children?: ReactNode;
}

export const PlayableHeader: FC<PlayableHeaderProps> = ({
  badgeUrl,
  children,
  systemIconUrl,
  systemLabel,
  title,
}) => {
  return (
    <div className="flex flex-col gap-3" data-testid="playable-header">
      <div className="flex gap-4 sm:gap-6">
        <img src={badgeUrl} className="size-16 rounded-sm sm:size-24" alt={title} />

        <div className="-mt-1 flex flex-col gap-4 sm:-mt-1.5">
          <div className="flex flex-col gap-1 sm:gap-0.5">
            <h1 className="text-h3 mb-0 border-b-0 text-lg sm:text-2xl">
              <GameTitle title={title} />
            </h1>

            <span className="flex items-center gap-1 whitespace-nowrap text-xs">
              <img src={systemIconUrl} alt="icon" width={18} height={18} />
              <span>{systemLabel}</span>
            </span>
          </div>

          <div className="hidden flex-wrap gap-x-2 gap-y-1 text-neutral-300 light:text-neutral-700 sm:flex">
            {children}
          </div>
        </div>
      </div>

      <div className="flex flex-wrap gap-x-2 gap-y-1 text-neutral-300 light:text-neutral-700 sm:hidden">
        {children}
      </div>
    </div>
  );
};
