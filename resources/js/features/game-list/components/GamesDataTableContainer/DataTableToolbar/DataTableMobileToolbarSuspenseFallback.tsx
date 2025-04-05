import type { FC } from 'react';

import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';

export const DataTableMobileToolbarSuspenseFallback: FC = () => {
  return (
    <div className="flex w-full flex-col justify-between gap-2 md:flex-row">
      <div className="flex items-center justify-between gap-3 md:justify-normal">
        <BaseSkeleton className="h-6 w-28 rounded-full" />

        <BaseSkeleton className="h-6 w-40" />
      </div>

      <BaseSkeleton className="h-8 w-full" />
    </div>
  );
};
