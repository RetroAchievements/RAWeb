import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { SimilarGamesListItem } from './SimilarGamesListItem';

interface SimilarGamesListProps {
  similarGames: App.Platform.Data.Game[];
}

export const SimilarGamesList: FC<SimilarGamesListProps> = ({ similarGames }) => {
  const { t } = useTranslation();

  if (!similarGames?.length) {
    return null;
  }

  return (
    <div data-testid="similar-games-list">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Similar Games')}</h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <ul className="zebra-list overflow-hidden rounded-lg">
          {similarGames.map((similarGame) => (
            <li
              key={`similar-games-list-item-${similarGame.id}`}
              className="w-full p-2 first:rounded-t-lg last:rounded-b-lg"
            >
              <SimilarGamesListItem game={similarGame} />
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};
