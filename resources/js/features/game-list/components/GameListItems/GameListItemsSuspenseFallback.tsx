import type { FC } from 'react';

import { LoadingGameListItem } from './LoadingGameListItem';

export const GameListItemsSuspenseFallback: FC = () => {
  return (
    <ol className="flex flex-col gap-2">
      {Array.from({ length: 8 }).map((_, index) => (
        <LoadingGameListItem key={`loading-${index}`} />
      ))}
    </ol>
  );
};
