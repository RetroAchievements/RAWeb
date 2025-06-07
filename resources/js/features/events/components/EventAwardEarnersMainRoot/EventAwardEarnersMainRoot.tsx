import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { AwardEarnersList } from '@/common/components/AwardEarnersList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { PlayableHeader } from '@/common/components/PlayableHeader';
import { usePageProps } from '@/common/hooks/usePageProps';
import { EventBreadcrumbs } from '@/features/events/components/EventBreadcrumbs';
import { cleanEventAwardLabel } from '@/features/events/utils/cleanEventAwardLabel';

export const EventAwardEarnersMainRoot: FC = memo(() => {
  const { event, eventAward, paginatedUsers } =
    usePageProps<App.Platform.Data.EventAwardEarnersPageProps>();

  const { t } = useTranslation();

  const cleanAwardLabel = cleanEventAwardLabel(eventAward.label, event);

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('event.award-earners.index', {
        event: event.id,
        tier: eventAward.tierIndex,
        _query: { page: newPageValue },
      }),
    );
  };

  return (
    <div>
      <EventBreadcrumbs
        event={event}
        t_currentPageLabel={t('{{awardLabel}}', { awardLabel: cleanAwardLabel })}
      />
      <PlayableHeader
        badgeUrl={eventAward.badgeUrl}
        systemLabel={event.legacyGame!.title}
        systemIconUrl="/assets/images/system/events.png"
        title={cleanAwardLabel}
      >
        <span>
          {t('{{val, number}} players have earned this', {
            val: eventAward.badgeCount,
            count: eventAward.badgeCount,
          })}
        </span>
      </PlayableHeader>

      <div className="mb-3 mt-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedUsers}
        />
      </div>

      <AwardEarnersList paginatedUsers={paginatedUsers} />

      <div className="mt-8 flex justify-center sm:mt-3 sm:justify-start">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedUsers}
        />
      </div>
    </div>
  );
});
