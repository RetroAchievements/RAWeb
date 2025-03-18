import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SystemGamesMainRoot } from '@/features/game-list/components/SystemGamesMainRoot';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';
import { buildSystemGamesMetaDescription } from '@/features/game-list/utils/buildSystemGamesMetaDescription';

const SystemGames: AppPage = () => {
  const { paginatedGameListEntries, persistedViewPreferences, system } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  return (
    <>
      <SEO
        title={t('All {{systemName}} Games', { systemName: system.name })}
        description={buildSystemGamesMetaDescription(paginatedGameListEntries.total, system.name)}
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
