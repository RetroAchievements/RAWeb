import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { PageMetaDescription } from '@/common/components/PageMetaDescription';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SystemGamesMainRoot } from '@/features/game-list/components/SystemGamesMainRoot';

const SystemGames: AppPage = () => {
  const { paginatedGameListEntries, system } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  const metaDescription = `Explore ${(Math.floor(paginatedGameListEntries.total / 100) * 100).toLocaleString()}+ ${system.name} games on RetroAchievements. Our achievements bring a fresh perspective to classic games, letting you track your progress as you beat and master each title.`;

  return (
    <>
      <Head title={t('All {{systemName}} Games', { systemName: system.name })}>
        <PageMetaDescription content={metaDescription} />
      </Head>

      <div className="container">
        <AppLayout.Main>
          <SystemGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

SystemGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SystemGames;
