import { type FC } from 'react';

import { BaseCommandGroup } from '@/common/components/+vendor/BaseCommand';
import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';

export const SearchResultsSkeleton: FC = () => {
  return (
    <>
      {Array.from({ length: 3 }).map((_, sectionIndex) => (
        <BaseCommandGroup
          key={`skeleton-section-${sectionIndex}`}
          heading={
            <div className="flex items-center justify-between">
              <BaseSkeleton className="h-4 w-20" />
              <BaseSkeleton className="h-3 w-16" />
            </div>
          }
        >
          {Array.from({ length: 3 }).map((_, itemIndex) => (
            <div
              key={`skeleton-item-${sectionIndex}-${itemIndex}`}
              className="flex items-center gap-3 px-2 py-3"
            >
              <BaseSkeleton className="size-8 rounded" />

              <div className="flex-1">
                <BaseSkeleton className="mb-1 h-4 w-3/4" />
                <BaseSkeleton className="h-3 w-1/2" />
              </div>
            </div>
          ))}
        </BaseCommandGroup>
      ))}
    </>
  );
};
