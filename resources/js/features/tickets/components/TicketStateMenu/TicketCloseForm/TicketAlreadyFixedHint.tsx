import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import { usePageProps } from '@/common/hooks/usePageProps';

interface TicketAlreadyFixedHintProps {
  isCoveringTicketSuggested: boolean;
}

export const TicketAlreadyFixedHint: FC<TicketAlreadyFixedHintProps> = ({
  isCoveringTicketSuggested,
}) => {
  const { alreadyFixedEvidence } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();
  const { formatCoarseDuration } = useFormatCoarseDuration();

  if (!alreadyFixedEvidence) {
    return null;
  }

  const { coveringTicketId, logicChangedAt, reporterSessionStartedAt } = alreadyFixedEvidence;

  return (
    <p className="text-xs text-neutral-400 light:text-neutral-600">
      {t(
        "The logic changed {{duration}} after the reporter's session started. They may have had the old logic loaded.",
        { duration: formatCoarseDuration(reporterSessionStartedAt, logicChangedAt) },
      )}

      {isCoveringTicketSuggested && coveringTicketId ? (
        <>
          {' '}
          <Trans
            i18nKey="The logic change probably fixed ticket <1>#{{ticketId}}</1>."
            values={{ ticketId: coveringTicketId }}
            components={{
              1: (
                <InertiaLink
                  href={route('ticket.show', { ticket: coveringTicketId })}
                  target="_blank"
                  rel="noreferrer"
                />
              ),
            }}
          />
        </>
      ) : null}
    </p>
  );
};
