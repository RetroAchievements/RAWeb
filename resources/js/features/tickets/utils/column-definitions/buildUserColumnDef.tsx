import type { ColumnDef } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildUserColumnDefProps {
  getUser: (entry: App.Platform.Data.TicketListEntry) => App.Data.User | null;
  id: string;
  t_label: TranslatedString;
}

export function buildUserColumnDef({
  getUser,
  id,
  t_label,
}: BuildUserColumnDefProps): ColumnDef<App.Platform.Data.TicketListEntry> {
  return {
    id,
    meta: {
      t_label,
      responsiveClassName: cn(
        'flex w-[11.5em] flex-none items-center',
        ticketListCellClassNames.userResponsive,
      ),
    },

    cell: ({ row }) => <UserCell user={getUser(row.original)} />,
  };
}

interface UserCellProps {
  user: App.Data.User | null;
}

const UserCell: FC<UserCellProps> = ({ user }) => {
  const { t } = useTranslation();

  if (!user) {
    return (
      <span className={cn(ticketListCellClassNames.dimText, ticketListCellClassNames.truncate)}>
        {t('Deleted user')}
      </span>
    );
  }

  // The user is secondary info about the ticket. Therefore, we mark the link
  // as neutral at rest and we only give it the link color on hover.
  return (
    <UserAvatar
      {...user}
      size={16}
      hasTooltip={false}
      wrapperClassName={cn('max-w-full min-w-0', ticketListCellClassNames.entityLinkWrapper)}
      labelClassName={cn(
        ticketListCellClassNames.entityLinkLabel,
        ticketListCellClassNames.truncate,
      )}
    />
  );
};
