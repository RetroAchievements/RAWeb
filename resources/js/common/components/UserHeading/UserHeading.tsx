import type { FC, ReactNode } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';

interface UserHeadingProps {
  children: ReactNode;
  user: App.Data.User;
}

export const UserHeading: FC<UserHeadingProps> = ({ children, user }) => {
  return (
    <div className="mb-3 flex w-full gap-x-3">
      <div className="inline">
        <UserAvatar {...user} showLabel={false} size={48} />
      </div>

      <h1 className="text-h3 mt-4 w-full sm:mt-2.5 sm:!text-[2.0em]">{children}</h1>
    </div>
  );
};
