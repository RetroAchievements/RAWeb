import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { EmptyState } from '@/common/components/EmptyState';

interface TicketListEmptyStateProps {
  onPrefetchViewAll?: () => void;
  onViewAll?: () => void;
  scope?: App.Platform.Enums.TicketListScope;
  unfilteredTotal?: number | null;
}

export const TicketListEmptyState: FC<TicketListEmptyStateProps> = ({
  onPrefetchViewAll,
  onViewAll,
  scope,
  unfilteredTotal,
}) => {
  const { t } = useTranslation();

  const hasTicketHistory = (unfilteredTotal ?? 0) > 0;

  let noTicketsMessage = t('There are no tickets in this list.');
  if (scope === 'game') {
    noTicketsMessage = t('No tickets have been reported for this game.');
  } else if (scope === 'achievement') {
    noTicketsMessage = t('No tickets have been reported for this achievement.');
  }

  return (
    <div className="rounded-[0.3em] bg-embed">
      <EmptyState shouldShowImage={false}>
        <span className="block text-neutral-400 light:text-neutral-600">
          {unfilteredTotal === 0 ? noTicketsMessage : t('No tickets match these filters.')}
        </span>

        {hasTicketHistory && onViewAll ? (
          <BaseButton
            variant="ghost"
            size="sm"
            className="mt-3 text-link"
            onClick={onViewAll}
            onMouseEnter={onPrefetchViewAll}
          >
            {t('View all tickets')}
          </BaseButton>
        ) : null}
      </EmptyState>
    </div>
  );
};
