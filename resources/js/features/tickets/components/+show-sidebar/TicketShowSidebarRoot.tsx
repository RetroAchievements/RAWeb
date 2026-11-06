import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { RelatedTicketsPanel } from '../RelatedTicketsPanel';
import { ReporterGameTicketsPanel } from '../ReporterGameTicketsPanel';
import { TicketPropertiesPanel } from '../TicketPropertiesPanel';

export const TicketShowSidebarRoot: FC = () => {
  const { relatedTickets, reporterGameTickets } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <TicketPropertiesPanel />

      {relatedTickets.length ? <RelatedTicketsPanel /> : null}

      {reporterGameTickets.length ? <ReporterGameTicketsPanel /> : null}
    </div>
  );
};
