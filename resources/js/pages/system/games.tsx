import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SystemGamesMainRoot } from '@/features/game-list/components/SystemGamesMainRoot';
import { buildSystemGamesMetaDescription } from '@/features/game-list/utils/buildSystemGamesMetaDescription';

const SystemGames: AppPage = () => {
  const { paginatedGameListEntries, system } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('All {{systemName}} Games', { systemName: system.name })}
        description={buildSystemGamesMetaDescription(paginatedGameListEntries.total, system.name)}
      />

      <AppLayout.Main>
        <SystemGamesMainRoot />
      </AppLayout.Main>
    </>
  );
};

SystemGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SystemGames;
