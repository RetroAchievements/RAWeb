import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { FullPaginator } from '@/common/components/FullPaginator';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading/GameHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { TopAchieversList } from './TopAchieversList';

export const TopAchieversMainRoot: FC = () => {
  const { game, paginatedUsers } = usePageProps<App.Platform.Data.GameTopAchieversPageProps>();

  const { t } = useTranslation();

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('game.top-achievers.index', {
        game: game.id,
        _query: { page: newPageValue },
      }),
    );
  };

  return (
    <div>
      <GameBreadcrumbs
        game={game}
        system={game.system}
        t_currentPageLabel={t('Game Top Achievers')}
      />
      <GameHeading game={game}>{t('Game Top Achievers')}</GameHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedUsers}
        />
      </div>

      <TopAchieversList />

      <div className="mt-8 flex justify-center sm:mt-3 sm:justify-start">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedUsers}
        />
      </div>
    </div>
  );
};
