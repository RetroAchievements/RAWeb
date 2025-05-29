import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

import { EventBreadcrumbs } from '@/features/events/components/EventBreadcrumbs';
import { FullPaginator } from '@/common/components/FullPaginator';
import { PlayableHeader } from '@/common/components/PlayableHeader';
import { AwardEarnersList } from '@/common/components/AwardEarnersList';

const AwardEarners: AppPage<App.Platform.Data.EventAwardEarnersPageProps> = ({ event, eventAward, paginatedUsers }) => {
  const { t } = useTranslation();

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('event.award-earners.index', {
        event: event.id,
        _query: { tier: eventAward.tierIndex, page: newPageValue },
      }),
    );
  };

  return (
    <>
      <SEO
        title={t('{{awardLabel}} Award Earners ({{eventTitle}})', { awardLabel: eventAward.label, eventTitle: event.legacyGame?.title })}
        description={`Players who have won the ${eventAward.label} for the ${event.legacyGame?.title} event`}
        ogImage={eventAward.badgeUrl}
      />

      <AppLayout.Main>
        <div>
          <EventBreadcrumbs event={event} t_currentPageLabel={t('{{awardLabel}}', { awardLabel: eventAward.label })} />
          <PlayableHeader
            badgeUrl={eventAward.badgeUrl}
            systemLabel={event.legacyGame?.title ?? 'Event'}
            systemIconUrl="/assets/images/system/events.png"
            title={eventAward.label}
          >
            <span>{t('{{val, number}} players have earned this', {
              val: eventAward.badgeCount,
              count: eventAward.badgeCount,
            })}</span>
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
      </AppLayout.Main>
    </>
  );
};

AwardEarners.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AwardEarners;
