import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { DeveloperFeedMainRoot } from '@/features/developer-feed/components/+root';

const DeveloperFeed: AppPage = () => {
  const { developer } = usePageProps<App.Community.Data.DeveloperFeedPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Developer Feed - {{user}}', { user: developer.displayName })}
        description={`View recent activity for achievements contributed by ${developer.displayName} on RetroAchievements`}
        ogImage={developer.avatarUrl}
      />

      <AppLayout.Main>
        <DeveloperFeedMainRoot />
      </AppLayout.Main>
    </>
  );
};

DeveloperFeed.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default DeveloperFeed;
