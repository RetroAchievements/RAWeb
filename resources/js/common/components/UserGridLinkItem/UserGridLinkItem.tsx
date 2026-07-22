import type { FC } from 'react';
import { route } from 'ziggy-js';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';

interface UserGridLinkItemProps {
  user: App.Data.User;

  isHighlighted?: boolean;
}

export const UserGridLinkItem: FC<UserGridLinkItemProps> = ({ user, isHighlighted = false }) => {
  return (
    <a
      href={route('user.show', { user: user.displayName })}
      className={cn(
        'group flex items-center gap-2 rounded-lg border p-2',
        isHighlighted
          ? 'border-amber-400/60 bg-amber-400/5 hover:border-amber-400'
          : 'border-embed-highlight bg-embed hover:border-neutral-300 hover:bg-embed-highlight',
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
