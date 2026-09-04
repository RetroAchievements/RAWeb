import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildUserColumnDefProps {
  getUser: (entry: App.Platform.Data.TicketListEntry) => App.Data.User | null;
  id: 'developer' | 'reporter' | 'resolver';
  t_label: TranslatedString;
}

export function buildUserColumnDef({
  getUser,
  id,
  t_label,
}: BuildUserColumnDefProps): TicketListColumnDefinition {
  return {
    id,
    meta: {
      t_label,
      responsiveClassName: cn(
        'flex w-[11.5em] flex-none items-center',
        ticketListCellClassNames.userResponsive,
      ),
    },

    cell: ({ row }) => (
      <UserCell
        user={getUser(row.original)}
        shouldHideWhenUserIsMissing={
          id === 'resolver' && row.original.state !== 'closed' && row.original.state !== 'resolved'
        }
      />
    ),
  };
}

interface UserCellProps {
  shouldHideWhenUserIsMissing: boolean;
  user: App.Data.User | null;
}

const UserCell: FC<UserCellProps> = ({ shouldHideWhenUserIsMissing, user }) => {
  const { t } = useTranslation();

  if (!user && shouldHideWhenUserIsMissing) {
    return null;
  }

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
