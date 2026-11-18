import type { FC } from 'react';

import { useTicketSignalVerdictCopy } from '../../hooks/useTicketSignalVerdictCopy';
import { TICKET_SIGNAL_VERDICT_APPEARANCE } from '../../utils/ticketSignalVerdictAppearance';
import { TicketCollapsibleSection } from '../TicketCollapsibleSection';

interface TicketSignalVerdictFactProps {
  reportedAt: string;
  signals: App.Platform.Data.TicketSignals;
}

export const TicketSignalVerdictFact: FC<TicketSignalVerdictFactProps> = ({
  reportedAt,
  signals,
}) => {
  const { accuracy, label, reasons } = useTicketSignalVerdictCopy(signals, reportedAt);

  const { Icon, className } = TICKET_SIGNAL_VERDICT_APPEARANCE[signals.verdict];

  return (
    <li>
      <TicketCollapsibleSection
        Icon={Icon}
        iconClassName={className}
        label={label}
        contentClassName="gap-1 py-2 pl-10"
      >
        {reasons.map((reason) => (
          <p key={reason}>{reason}</p>
        ))}

        <p className="text-neutral-400 light:text-neutral-500">{accuracy}</p>
      </TicketCollapsibleSection>
    </li>
  );
};
