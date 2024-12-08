import type { FC, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

import { AchievementAvatar } from '../AchievementAvatar';

interface AchievementHeadingProps {
  children: ReactNode;
  achievement: App.Platform.Data.Achievement;

  wrapperClassName?: string;
}

export const AchievementHeading: FC<AchievementHeadingProps> = ({
  children,
  achievement,
  wrapperClassName,
}) => {
  return (
    <div className={cn('mb-3 flex w-full gap-x-3', wrapperClassName)}>
      <div className="mb-2 inline self-end">
        <AchievementAvatar {...achievement} showLabel={false} size={48} />
      </div>

      <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">{children}</h1>
    </div>
  );
};
