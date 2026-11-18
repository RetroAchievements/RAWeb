import { type FC, useId } from 'react';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';

import { useTicketSignalVerdictCopy } from '../../hooks/useTicketSignalVerdictCopy';
import { TICKET_SIGNAL_VERDICT_APPEARANCE } from '../../utils/ticketSignalVerdictAppearance';

interface TicketSignalVerdictProps {
  /**
   * For mobile vs desktop UX.
   */
  hasTooltip: boolean;
  reportedAt: string;
  signals: App.Platform.Data.TicketSignals;
}

export const TicketSignalVerdict: FC<TicketSignalVerdictProps> = ({
  signals,
  reportedAt,
  hasTooltip,
}) => {
  const { label, reasons, accuracy } = useTicketSignalVerdictCopy(signals, reportedAt);
  const evidenceId = useId();

  const verdictKind = signals.verdict;
  const { Icon, className } = TICKET_SIGNAL_VERDICT_APPEARANCE[verdictKind];

  // For a11y.
  const hiddenEvidence = (
    <span id={evidenceId} hidden={true}>
      {[...reasons, accuracy].join(' ')}
    </span>
  );

  const verdictIcon = <Icon className="size-4" aria-hidden="true" />;

  if (!hasTooltip) {
    return (
      <>
        <span
          role="img"
          aria-label={label}
          aria-describedby={evidenceId}
          data-verdict={verdictKind}
          className={cn('flex items-center justify-center', className)}
        >
          {verdictIcon}
        </span>

        {hiddenEvidence}
      </>
    );
  }

  return (
    <>
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
          <button
            type="button"
            aria-label={label}
            aria-describedby={evidenceId}
            data-verdict={verdictKind}
            className={cn(
              'relative z-10 flex items-center justify-center rounded-sm',
              'before:absolute before:inset-[-0.5em] before:content-[""]',
              'focus-visible:outline-2',
              className,
            )}
          >
            {verdictIcon}
          </button>
        </BaseTooltipTrigger>

        <BaseTooltipContent className="flex max-w-xs flex-col gap-1">
          <p className="font-semibold">{label}</p>

          <ul className="flex flex-col gap-1">
            {reasons.map((reason) => (
              <li key={reason}>{reason}</li>
            ))}
          </ul>

          <p className="text-neutral-400 light:text-neutral-500">{accuracy}</p>
        </BaseTooltipContent>
      </BaseTooltip>

      {hiddenEvidence}
    </>
  );
};
