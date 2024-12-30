import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

export const TrendingRightNow: FC = () => {
  const { trendingGames } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!trendingGames?.length) {
    return null;
  }

  return (
    <div>
      <p className="text-xs font-bold">{t('Trending right now')}</p>

      <div className="grid gap-1 sm:grid-cols-2">
        {trendingGames.map((trendingGame) => (
          <div key={`trending-${trendingGame.game.id}`} className="rounded-lg bg-embed p-2">
            <div className="relative flex w-full items-end justify-between">
              <GameAvatar
                {...trendingGame.game}
                size={40}
                showSystemChip={true}
                gameTitleClassName="line-clamp-1"
              />

              <p className="absolute bottom-0 right-0 text-2xs">
                {t('playerCount', {
                  count: trendingGame.playerCount,
                  val: trendingGame.playerCount,
                })}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
