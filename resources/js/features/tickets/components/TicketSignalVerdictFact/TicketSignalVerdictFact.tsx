import type { FC, ReactNode } from 'react';

import { useTicketSignalVerdictCopy } from '../../hooks/useTicketSignalVerdictCopy';
import { getIsUnlockWindowSignal } from '../../utils/getIsUnlockWindowSignal';

interface TicketSignalVerdictFactProps {
  reportedAt: string;
  signals: App.Platform.Data.TicketSignals;

  unlockCountFollowUp?: ReactNode;
}

export const TicketSignalVerdictFact: FC<TicketSignalVerdictFactProps> = ({
  reportedAt,
  signals,
  unlockCountFollowUp,
}) => {
  const { accuracy, label, reasons } = useTicketSignalVerdictCopy(signals, reportedAt);

  return (
    <>
      <span className="block font-semibold">{label}</span>

      {reasons.map((reason, index) => (
        <span key={reason} className="block">
          {reason}
          {unlockCountFollowUp && getIsUnlockWindowSignal(signals.kinds[index]) ? (
            <> {unlockCountFollowUp}</>
          ) : null}
        </span>
      ))}

      <span className="block text-neutral-400 light:text-neutral-500">{accuracy}</span>
    </>
  );
};
