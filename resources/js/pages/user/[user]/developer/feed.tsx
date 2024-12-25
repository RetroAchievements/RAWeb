import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { DeveloperFeedMainRoot } from '@/features/developer-feed/components/+root';

const DeveloperFeed: AppPage = () => {
  const { developer } = usePageProps<App.Community.Data.DeveloperFeedPageProps>();

  const { t } = useTranslation();

  const metaDescription = `View recent activity for achievements contributed by ${developer.displayName} on RetroAchievements`;

  return (
    <>
      {/* TODO SEO component */}
      <Head title={t('Developer Feed - {{user}}', { user: developer.displayName })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={developer.avatarUrl} />
      </Head>

      <AppLayout.Main>
        <DeveloperFeedMainRoot />
      </AppLayout.Main>
    </>
  );
};

DeveloperFeed.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default DeveloperFeed;
