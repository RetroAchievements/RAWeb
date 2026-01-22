import * as motion from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';
import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

type ViewMode = 'trending' | 'popular';

export const GameActivity: FC = () => {
  const { popularGames, trendingGames } = usePageProps<App.Http.Data.HomePageProps>();
  const { t } = useTranslation();

  const hasTrending = trendingGames?.length > 0;
  const hasPopular = popularGames?.length > 0;

  const [viewMode, setViewMode] = useState<ViewMode>(hasTrending ? 'trending' : 'popular');
  const [hasUserToggled, setHasUserToggled] = useState(false);

  // Bail if we have no data to show.
  if (!hasTrending && !hasPopular) {
    return null;
  }

  const games = viewMode === 'trending' ? trendingGames : popularGames;

  const handleValueChange = (value: string): void => {
    if (!value) {
      return;
    }

    setViewMode(value as ViewMode);
    setHasUserToggled(true);
  };

  return (
    <div>
      <div className="mb-0.5 flex w-full items-center justify-between">
        <p className="text-xs font-bold">
          {viewMode === 'trending' ? t('Trending right now') : t('Popular right now')}
        </p>

        <BaseToggleGroup
          type="single"
          className="gap-px"
          value={viewMode}
          onValueChange={handleValueChange}
        >
          <BaseToggleGroupItem
            size="sm"
            value="popular"
            aria-label={t('Toggle popular')}
            className="h-[24px] px-1.5 text-2xs"
            disabled={!hasPopular}
          >
            {t('Popular')}
          </BaseToggleGroupItem>

          <BaseToggleGroupItem
            size="sm"
            value="trending"
            aria-label={t('Toggle trending')}
            className="h-[24px] px-1.5 text-2xs"
            disabled={!hasTrending}
          >
            {t('Trending')}
          </BaseToggleGroupItem>
        </BaseToggleGroup>
      </div>

      <motion.div
        className="grid gap-1 sm:grid-cols-2"
        animate={hasUserToggled ? { opacity: [0.7, 1] } : undefined}
        transition={{ duration: 0.3 }}
        key={viewMode}
      >
        {games.map((game) => (
          <div key={`${viewMode}-${game.game.id}`} className="rounded-lg bg-embed p-2">
            <div className="relative flex w-full items-end justify-between">
              <GameAvatar
                {...game.game}
                size={40}
                showSystemChip={true}
                gameTitleClassName="line-clamp-1"
              />

              <p className="absolute bottom-0 right-0 text-2xs">
                {viewMode === 'trending' && game.trendingReason
                  ? t(game.trendingReason)
                  : t('playerCount', {
                      count: game.playerCount,
                      val: game.playerCount,
                    })}
              </p>
            </div>
          </div>
        ))}
      </motion.div>
    </div>
  );
};
