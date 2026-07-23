import type { FC } from 'react';
import { route } from 'ziggy-js';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';

interface UserGridLinkItemProps {
  user: App.Data.User;
}

export const UserGridLinkItem: FC<UserGridLinkItemProps> = ({ user }) => {
  return (
    <a
      href={route('user.show', { user: user.displayName })}
      className={cn(
        'group flex items-center gap-2 rounded-lg border border-embed-highlight bg-embed p-2',
        'hover:border-neutral-300 hover:bg-embed-highlight',
      )}
    >
      <UserAvatar
        {...user}
        size={40}
        labelClassName="group-hover:text-link-hover"
        canLinkToUser={false}
      />
    </a>
  );
};
