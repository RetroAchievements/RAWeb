import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { LuRss } from 'react-icons/lu';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const Rss: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('RSS Feeds')}>
        <meta
          name="description"
          content="Stay updated with the latest news and achievements from RetroAchievements. Access our RSS feed for real-time updates on community events."
        />
      </Head>

      <div className="container">
        <AppLayout.Main>
          {/* TODO as more feeds are added here, consider breaking this out to a component in a feature module */}
          <h1 className="mb-4">{'RSS'}</h1>

          <a href="/rss-news" className="flex items-center gap-1">
            <LuRss />
            {t('News')}
          </a>
        </AppLayout.Main>
      </div>
    </>
  );
};

Rss.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Rss;
