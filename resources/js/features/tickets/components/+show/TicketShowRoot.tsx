import type { FC } from 'react';

import { TicketComments } from '../TicketComments';
import { TicketFactsList } from '../TicketFactsList';
import { TicketManualUnlockRequestNotice } from '../TicketManualUnlockRequestNotice';
import { TicketPlayerHistory } from '../TicketPlayerHistory';
import { TicketShowBreadcrumbs } from '../TicketShowBreadcrumbs';
import { TicketShowHeader } from '../TicketShowHeader';

export const TicketShowRoot: FC = () => {
  return (
    <div>
      <TicketShowBreadcrumbs />

      <div className="flex flex-col gap-4">
        <TicketShowHeader />
        <TicketManualUnlockRequestNotice />
        <TicketFactsList />
        <TicketPlayerHistory />
        <TicketComments />
      </div>
    </div>
  );
};
