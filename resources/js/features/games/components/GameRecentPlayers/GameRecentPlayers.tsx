import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { GameRecentPlayersList } from './GameRecentPlayersList';
import { GameRecentPlayersTable } from './GameRecentPlayersTable';

export const GameRecentPlayers: FC = () => {
  const { recentPlayers } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  if (!recentPlayers.length) {
    return null;
  }

  return (
    <div>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Recent Players')}</h2>

      <div className="rounded-lg bg-embed p-1">
        <div className="flex flex-col gap-2 p-1 sm:hidden">
          <GameRecentPlayersList />
        </div>

        <GameRecentPlayersTable />
      </div>
    </div>
  );
};
