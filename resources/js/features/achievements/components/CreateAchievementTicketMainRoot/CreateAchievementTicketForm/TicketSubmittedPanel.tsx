import type { FC } from 'react';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { RequestManualUnlockLink } from './RequestManualUnlockLink';

interface TicketSubmittedPanelProps {
  ticketId: number;
}

export const TicketSubmittedPanel: FC<TicketSubmittedPanelProps> = ({ ticketId }) => {
  const { t } = useTranslation();

  const titleRef = useRef<HTMLHeadingElement>(null);

  // pop focus
  useEffect(() => {
    titleRef.current?.focus();
  }, []);

  const ticketDetailUrl = route('ticket.show', { ticket: ticketId });

  return (
    <section className="flex flex-col gap-3 rounded-sm bg-embed p-3">
      <h2
        ref={titleRef}
        tabIndex={-1}
        className="mb-0 border-b-0 text-lg font-semibold text-neutral-200 outline-none light:text-neutral-900"
      >
        {t('Ticket submitted')}
      </h2>

      <div className="flex flex-col gap-1 text-neutral-300 light:text-neutral-700">
        <p>
          {t(
            'A ticket tells the developer about the bug. It does not add the achievement to your profile.',
          )}
        </p>
        <p>{t("If you completed this achievement's requirement, request a manual unlock.")}</p>
        <p>
          {t(
            'You need proof: a screenshot of the achievement popup, a video, or a later achievement.',
          )}
        </p>
      </div>

      <div className="flex flex-wrap items-center gap-3">
        <RequestManualUnlockLink ticketId={ticketId} />

        <a href={ticketDetailUrl}>{t('View your ticket')}</a>
      </div>
    </section>
  );
};
