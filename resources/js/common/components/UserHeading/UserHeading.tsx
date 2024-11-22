import type { FC, ReactNode } from 'react';

import { cn } from '@/utils/cn';

import { UserAvatar } from '../UserAvatar';

interface UserHeadingProps {
  children: ReactNode;
  user: App.Data.User;

  wrapperClassName?: string;
}

export const UserHeading: FC<UserHeadingProps> = ({ children, user, wrapperClassName }) => {
  return (
    <div className={cn('mb-3 flex w-full gap-x-3', wrapperClassName)}>
      <div className="inline">
        <UserAvatar {...user} showLabel={false} size={48} />
      </div>

      <h1 className="text-h3 mt-4 w-full sm:mt-2.5 sm:!text-[2.0em]">{children}</h1>
    </div>
  );
};
