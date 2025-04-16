import { type FC, useId } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { AvatarSize } from '@/common/models';
import { cn } from '@/common/utils/cn';
import { getUserIntlLocale } from '@/common/utils/getUserIntlLocale';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { UserAvatar } from '../UserAvatar';

interface UserAvatarStackProps {
  users: App.Data.User[];

  size?: AvatarSize;

  /**
   * Maximum number of avatars to display. If there are more than this many
   * users, we'll show (maxVisible - 1) avatars and a "+N" indicator.
   *
   * In other words, if maxVisible is 5 and we have 5 users, we'll show all
   * 5 avatars. If we have 6 users, we'll show 4 avatars and a "+2" label.
   */
  maxVisible?: number;
}

export const UserAvatarStack: FC<UserAvatarStackProps> = ({ users, maxVisible = 5, size = 32 }) => {
  const { auth } = usePageProps();

  const id = useId();

  if (!users.length) {
    return null;
  }

  const userIntlLocale = getUserIntlLocale(auth?.user);

  const visibleCount = users.length > maxVisible ? maxVisible - 1 : maxVisible;
  const visibleUsers = users.slice(0, visibleCount);
  const remainingUsers = users.slice(visibleCount);
  const remainingCount = remainingUsers.length;

  const formatter = new Intl.ListFormat(userIntlLocale, { style: 'long', type: 'conjunction' });
  const remainingNames = formatter.format(remainingUsers.map((user) => user.displayName).sort());

  const numberFormatter = new Intl.NumberFormat(userIntlLocale, { signDisplay: 'always' });
  const formattedCount = numberFormatter.format(remainingCount);

  return (
    <div className="flex -space-x-2.5" role="list">
      {visibleUsers.map((user) => (
        <UserAvatar
          key={`user-avatar-stack-${id}-${user.displayName}`}
          {...user}
          size={size}
          showLabel={false}
          imgClassName="rounded-full ring-2 ring-neutral-800 light:ring-neutral-300"
        />
      ))}

      {remainingCount > 0 ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <div
              data-testid="overflow-indicator"
              className={cn(
                'flex items-center justify-center rounded-full text-[10px]',
                'font-mono tracking-tight ring-2',

                'bg-neutral-800 text-neutral-300 ring-neutral-700',
                'light:bg-neutral-200 light:text-neutral-700 light:ring-neutral-300',

                // TODO reusable avatar size helper
                size === 24 ? 'size-6' : null,
                size === 28 ? 'size-7' : null,
                size === 32 ? 'size-8' : null,
              )}
            >
              {formattedCount}
            </div>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="max-w-[300px] text-pretty">
            <p>{remainingNames}</p>
          </BaseTooltipContent>
        </BaseTooltip>
      ) : null}
    </div>
  );
};
