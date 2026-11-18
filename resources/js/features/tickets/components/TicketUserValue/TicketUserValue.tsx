import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';

import { ticketShowEntityLinkClassNames } from '../../utils/ticketShowEntityLinkClassNames';

interface TicketUserValueProps {
  user: App.Data.User | null | undefined;

  variant?: 'link' | 'quiet';
}

export const TicketUserValue: FC<TicketUserValueProps> = ({ user, variant = 'link' }) => {
  const { t } = useTranslation();

  if (!user) {
    return <span className="text-neutral-500">{t('Deleted user')}</span>;
  }

  return (
    <UserAvatar
      {...user}
      size={16}
      wrapperClassName={cn(
        'inline-flex align-middle',
        variant === 'quiet' && ticketShowEntityLinkClassNames.wrapper,
      )}
      labelClassName={variant === 'quiet' ? ticketShowEntityLinkClassNames.label : undefined}
    />
  );
};
