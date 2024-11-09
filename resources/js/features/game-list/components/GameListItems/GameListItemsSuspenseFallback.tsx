import type { FC } from 'react';

import { LoadingGameListItemContent } from './GameListItemElement/GameListItemContent/LoadingGameListItemContent';

export const GameListItemsSuspenseFallback: FC = () => {
  return (
    <ol className="flex flex-col gap-2">
      {Array.from({ length: 8 }).map((_, index) => (
        <LoadingGameListItemContent key={`loading-${index}`} />
      ))}
    </ol>
  );
};
