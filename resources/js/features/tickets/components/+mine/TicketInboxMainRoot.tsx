import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { TicketInboxSection } from '../TicketInboxSection';

type SectionKind = App.Platform.Enums.TicketInboxSectionKind;

export const TicketInboxMainRoot: FC = () => {
  const { attentionCount, sections, user } = usePageProps<App.Platform.Data.TicketInboxPageProps>();
  const { t } = useTranslation();

  const displayName = user.displayName;

  const headingLabelMap: Record<SectionKind, TranslatedString> = {
    toResolve: t('Waiting on you'),
    awaitingYourFeedback: t('Waiting on your feedback'),
    awaitingReporter: t('Waiting on the reporter'),
    reportedOpen: t('Open tickets you reported'),
    resolvedByYou: t('Resolved by you'),
  };

  const emptyMessageLabelMap: Partial<Record<SectionKind, TranslatedString>> = {
    toResolve: t('No tickets are waiting on you right now.'),
    awaitingYourFeedback: t('No tickets are waiting on your feedback.'),
  };

  const counterpartyColumnIds: Record<SectionKind, 'developer' | 'reporter'> = {
    toResolve: 'reporter',
    awaitingYourFeedback: 'developer',
    awaitingReporter: 'reporter',
    reportedOpen: 'developer',
    resolvedByYou: 'reporter',
  };

  const viewAllHrefs: Record<SectionKind, string> = {
    toResolve: route('developer.tickets2', { user: displayName, 'filter[status]': 'open' }),
    awaitingYourFeedback: route('reporter.tickets2', { user: displayName }),
    awaitingReporter: route('developer.tickets2', {
      user: displayName,
      'filter[status]': 'request',
    }),
    reportedOpen: route('user.tickets2.created', {
      user: displayName,
      'filter[status]': 'open',
    }),
    resolvedByYou: route('developer.tickets2.resolved', {
      user: displayName,
      'filter[status]': 'resolved',
    }),
  };

  return (
    <div className="flex flex-col gap-8">
      <div className="flex flex-col gap-1">
        <div className="flex w-full">
          <h1 className="text-h3 w-full sm:text-[2.0em]!">{t('Tickets')}</h1>
        </div>

        <p className="text-neutral-200 light:text-neutral-900">
          {attentionCount
            ? t('{{val, number}} tickets need your attention.', {
                count: attentionCount,
                val: attentionCount,
              })
            : t('Nothing needs your attention right now.')}
        </p>
      </div>

      <div className="flex flex-col gap-8">
        {sections.map((section) => (
          <TicketInboxSection
            key={section.kind}
            counterpartyColumnId={counterpartyColumnIds[section.kind]}
            section={section}
            t_emptyMessage={emptyMessageLabelMap[section.kind]}
            t_heading={headingLabelMap[section.kind]}
            viewAllHref={viewAllHrefs[section.kind]}
          />
        ))}
      </div>
    </div>
  );
};
