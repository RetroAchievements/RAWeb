import type { FC } from 'react';

import { useTicketSignalVerdictCopy } from '../../hooks/useTicketSignalVerdictCopy';

interface TicketSignalVerdictFactProps {
  reportedAt: string;
  signals: App.Platform.Data.TicketSignals;
}

export const TicketSignalVerdictFact: FC<TicketSignalVerdictFactProps> = ({
  reportedAt,
  signals,
}) => {
  const { accuracy, label, reasons } = useTicketSignalVerdictCopy(signals, reportedAt);

  return (
    <>
      <span className="block font-semibold">{label}</span>

      {reasons.map((reason) => (
        <span key={reason} className="block">
          {reason}
        </span>
      ))}

      <span className="block text-neutral-400 light:text-neutral-500">{accuracy}</span>
    </>
  );
};
