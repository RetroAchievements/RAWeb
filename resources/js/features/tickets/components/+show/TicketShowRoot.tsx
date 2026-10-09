import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { TicketFactsList } from '../TicketFactsList';
import { TicketShowBreadcrumbs } from '../TicketShowBreadcrumbs';
import { TicketShowHeader } from '../TicketShowHeader';

export const TicketShowRoot: FC = () => {
  const { ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();

  return (
    <div className="flex flex-col gap-4">
      <TicketShowBreadcrumbs ticket={ticket} />
      <TicketShowHeader />
      <TicketFactsList />
    </div>
  );
};
