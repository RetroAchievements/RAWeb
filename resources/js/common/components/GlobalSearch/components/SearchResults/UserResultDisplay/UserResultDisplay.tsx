import dayjs from 'dayjs';
import { type FC, useRef } from 'react';
import { useTranslation } from 'react-i18next';

import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

interface UserResultDisplayProps {
  user: App.Data.User;
}

export const UserResultDisplay: FC<UserResultDisplayProps> = ({ user }) => {
  const isActive = user.lastActivityAt
    ? Math.abs(dayjs(user.lastActivityAt).diff(dayjs(), 'minute')) <= 5
    : false;

  return (
    <div className="flex w-full items-center gap-3">
      <div className="relative">
        <img src={user.avatarUrl} alt={user.displayName} className="size-10 rounded" />

        {isActive ? (
          <div
            data-testid="active-indicator"
            className="absolute -right-1 -top-1 size-3 rounded-full bg-green-500"
          />
        ) : null}
      </div>

      <div className="flex flex-col gap-0.5">
        <div className="font-medium text-link">{user.displayName}</div>

        <div className="flex items-center gap-4 text-xs text-neutral-400 light:text-neutral-600">
          {user.lastActivityAt ? <LastSeenLabel userLastActivityAt={user.lastActivityAt} /> : null}
        </div>
      </div>
    </div>
  );
};

interface LastSeenLabelProps {
  userLastActivityAt: string;
}

const LastSeenLabel: FC<LastSeenLabelProps> = ({ userLastActivityAt }) => {
  const { t } = useTranslation();

  // We don't want the label to re-render.
  const lastActivityAt = useRef(userLastActivityAt);

  const { diffForHumans } = useDiffForHumans();

  return (
    <span>
      {t('Last seen {{lastSeenDate}}', { lastSeenDate: diffForHumans(lastActivityAt.current) })}
    </span>
  );
};
