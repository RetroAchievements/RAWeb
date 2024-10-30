import type { FC } from 'react';

import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';

/**
 * This should mirror the layout structure of <GameListItemElement />.
 */

interface LoadingGameListItemProps {
  /** If it's the last item, don't show a border at the bottom. */
  isLastItem?: boolean;
}

export const LoadingGameListItem: FC<LoadingGameListItemProps> = ({ isLastItem }) => {
  return (
    <div>
      <div className="flex items-center gap-3">
        <BaseSkeleton className="h-12 w-12 rounded-sm" />

        <div className="flex-grow">
          <div className="flex flex-col gap-1.5">
            <BaseSkeleton className="h-4 w-40" />
            <div className="flex gap-1">
              <BaseSkeleton className="h-[20px] w-16 rounded-full" />
            </div>
          </div>
        </div>

        <div className="-mr-1 flex gap-3 self-center">
          <BaseSkeleton className="h-5 w-5" />
          <BaseSkeleton className="h-5 w-5" />
        </div>
      </div>

      {isLastItem ? null : <BaseSkeleton className="ml-14 mt-2 h-px" />}
    </div>
  );
};
