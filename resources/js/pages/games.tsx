import { Head } from '@inertiajs/react';
import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AllGamesMainRoot } from '@/features/game-list/components/AllGamesMainRoot';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const AllGames: AppPage = () => {
  const { paginatedGameListEntries, persistedViewPreferences } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  const metaDescription = `Browse our catalog of ${(Math.floor(paginatedGameListEntries.total / 100) * 100).toLocaleString()}+ retro games with achievements. View detailed listings with achievement counts, points, rarity scores, and release dates.`;

  return (
    <>
      <Head title={t('All Games')}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />
      </Head>

      <div className="container">
        <AppLayout.Main>
          <AllGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

AllGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AllGames;
