import * as motion from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';
import { GameAvatar } from '@/common/components/GameAvatar';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

type ViewMode = 'trending' | 'popular';

export const GameActivity: FC = () => {
  const { popularGameSnapshots, trendingGameSnapshots } =
    usePageProps<App.Http.Data.HomePageProps>();
  const { t } = useTranslation();

  const hasTrending = trendingGameSnapshots?.length > 0;
  const hasPopular = popularGameSnapshots?.length > 0;

  const [viewMode, setViewMode] = useState<ViewMode>(hasTrending ? 'trending' : 'popular');
  const [hasUserToggled, setHasUserToggled] = useState(false);

  // The toggle group and cards would be empty, so render nothing.
  if (!hasTrending && !hasPopular) {
    return null;
  }

  const snapshots = viewMode === 'trending' ? trendingGameSnapshots : popularGameSnapshots;

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
        {snapshots.map((snapshot) => (
          <div key={`${viewMode}-${snapshot.game.id}`} className="rounded-lg bg-embed p-2">
            <div className="relative flex w-full items-end justify-between">
              <GameAvatar
                {...snapshot.game}
                size={40}
                showSystemChip={true}
                gameTitleClassName="line-clamp-1"
              />

              <SnapshotLabel snapshot={snapshot} viewMode={viewMode} />
            </div>
          </div>
        ))}
      </motion.div>
    </div>
  );
};

interface SnapshotLabelProps {
  snapshot: App.Community.Data.GameActivitySnapshot;
  viewMode: ViewMode;
}

const SnapshotLabel: FC<SnapshotLabelProps> = ({ snapshot, viewMode }) => {
  const { t } = useTranslation();

  if (viewMode === 'trending' && snapshot.event?.legacyGame) {
    return (
      <InertiaLink
        href={route('event.show', { event: snapshot.event.id })}
        className="absolute bottom-0 right-0 text-2xs text-link"
      >
        {snapshot.event.legacyGame.title}
      </InertiaLink>
    );
  }

  if (viewMode === 'trending' && snapshot.trendingReason) {
    return <p className="absolute bottom-0 right-0 text-2xs">{t(snapshot.trendingReason)}</p>;
  }

  return (
    <p className="absolute bottom-0 right-0 text-2xs">
      {t('playerCount', { count: snapshot.playerCount, val: snapshot.playerCount })}
    </p>
  );
};
