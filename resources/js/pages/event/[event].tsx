import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { EventShowMainRoot } from '@/features/events/components/+show';
import { EventShowSidebarRoot } from '@/features/events/components/+show-sidebar';
import { EventDesktopBanner } from '@/features/events/components/EventDesktopBanner';
import { buildEventMetaDescription } from '@/features/events/utils/buildEventMetaDescription';
import type { TranslatedString } from '@/types/i18next';

const EventShow: AppPage = () => {
  const { event, ziggy } = usePageProps<App.Platform.Data.EventShowPageProps>();

  return (
    <>
      <SEO
        title={event.legacyGame!.title as TranslatedString}
        description={buildEventMetaDescription(event)}
        ogImage={event.legacyGame!.badgeUrl}
      />

      {ziggy.device === 'desktop' ? (
        <AppLayout.Banner className="md:-mb-[30px]">
          <EventDesktopBanner />
        </AppLayout.Banner>
      ) : null}

      <AppLayout.Main>
        <EventShowMainRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <EventShowSidebarRoot />
      </AppLayout.Sidebar>
    </>
  );
};

EventShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default EventShow;
