import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';
import { HubMainRoot } from '@/features/game-list/components/HubMainRoot';

const Hub: AppPage = () => {
  const { hub, paginatedGameListEntries } = usePageProps<App.Platform.Data.HubPageProps>();

  const { t } = useTranslation();

  const metaDescription = `A collection of ${paginatedGameListEntries.total.toLocaleString()} ${paginatedGameListEntries.total === 1 ? 'game' : 'games'} on RetroAchievements.`;

  let pageTitle = t('All Hubs');
  const hubTitle = cleanHubTitle(hub.title!);
  if (hubTitle !== 'Central') {
    pageTitle = t('{{hubTitle}} (Hub)', { hubTitle });
  }

  return (
    <>
      <Head title={pageTitle}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={hub.badgeUrl!} />
        <meta property="og:type" content="retroachievements:hub" />
      </Head>

      <AppLayout.Main>
        <HubMainRoot />
      </AppLayout.Main>
    </>
  );
};

Hub.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Hub;
