import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { EventCommentsMainRoot } from '@/features/comments/EventCommentsMainRoot';

const EventComments: AppPage<App.Community.Data.EventCommentsPageProps> = ({ event }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Comments - {{title}}', { title: event.legacyGame?.title })}
        description={`General discussion about the event ${event.legacyGame?.title}`}
        ogImage={event.legacyGame?.badgeUrl}
      />

      <AppLayout.Main>
        <EventCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

EventComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default EventComments;
