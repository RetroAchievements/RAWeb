import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SystemGamesMainRoot } from '@/features/game-list/components/SystemGamesMainRoot';

const SystemGames: AppPage = () => {
  const { paginatedGameListEntries, system } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('All {{systemName}} Games', { systemName: system.name })}
        description={`Explore ${(Math.floor(paginatedGameListEntries.total / 100) * 100).toLocaleString()}+ ${system.name} games on RetroAchievements. Track your progress as you beat and master each title.`}
      />

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
