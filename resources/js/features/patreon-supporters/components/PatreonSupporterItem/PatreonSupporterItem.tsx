import type { FC } from 'react';
import { route } from 'ziggy-js';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';

interface PatreonSupporterItemProps {
  supporter: App.Data.User;
}

export const PatreonSupporterItem: FC<PatreonSupporterItemProps> = ({ supporter }) => {
  return (
    <a
      href={route('user.show', { user: supporter.displayName })}
      className={cn(
        'group flex items-center gap-2 rounded-lg border border-embed-highlight bg-embed p-2',
        'hover:border-neutral-300 hover:bg-embed-highlight',
      )}
    >
      <UserAvatar
        {...supporter}
        size={40}
        labelClassName="group-hover:text-link-hover"
        canLinkToUser={false}
      />
    </a>
  );
};
