import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { EventAwardEarnersMainRoot } from '@/features/events/components/EventAwardEarnersMainRoot/EventAwardEarnersMainRoot';

const AwardEarners: AppPage<App.Platform.Data.EventAwardEarnersPageProps> = ({
  event,
  eventAward,
}) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('{{awardLabel}} Award Earners ({{eventTitle}})', {
          awardLabel: eventAward.label,
          eventTitle: event.legacyGame?.title,
        })}
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
