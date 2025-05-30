import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

import { EventAwardEarnersMainRoot } from '@/features/events/components/EventAwardEarnersMainRoot/EventAwardEarnersMainRoot';

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
        <EventAwardEarnersMainRoot />
      </AppLayout.Main>
    </>
  );
};

AwardEarners.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AwardEarners;
