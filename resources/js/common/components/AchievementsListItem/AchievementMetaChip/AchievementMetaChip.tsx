import { forwardRef, type HTMLAttributes } from 'react';

import { cn } from '@/common/utils/cn';

export const AchievementMetaChip = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <div
      ref={ref}
      className={cn(
        'flex items-center rounded-full bg-embed p-1 text-neutral-200 light:text-neutral-500',
        className,
      )}
      {...props}
    />
  ),
);
