import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';

/**
 * This should mirror the layout structure of <GameListItemElement />.
 */

interface LoadingGameListItemContentProps {
  /** If it's the last item, don't show a border at the bottom. */
  isLastItem?: boolean;
}

export const LoadingGameListItemContent: FC<LoadingGameListItemContentProps> = ({ isLastItem }) => {
  const { t } = useLaravelReactI18n();

  return (
    <div role="status" aria-label={t('Loading...')}>
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

        <div className="flex gap-3 self-center pr-0.5">
          <BaseSkeleton className="size-7" />
          <BaseSkeleton className="size-7" />
        </div>
      </div>

      {isLastItem ? null : <BaseSkeleton data-testid="bottom-border" className="ml-14 mt-2 h-px" />}
    </div>
  );
};
