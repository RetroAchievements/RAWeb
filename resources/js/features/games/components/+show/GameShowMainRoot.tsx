import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuPackage, LuSettings, LuTags } from 'react-icons/lu';

import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { PlayableHeader } from '@/common/components/PlayableHeader';
import { PlayableMainMedia } from '@/common/components/PlayableMainMedia';
import { PlayableMobileMediaCarousel } from '@/common/components/PlayableMobileMediaCarousel';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameAchievementSetsContainer } from '../GameAchievementSetsContainer';
import { PrimaryMetadataChip } from '../PrimaryMetadataChip';
import { ReleasedAtChip } from '../ReleasedAtChip';

export const GameShowMainRoot: FC = () => {
  const { game, hubs } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  if (!game.badgeUrl || !game.system?.iconUrl) {
    return null;
  }

  return (
    <div className="flex flex-col gap-3">
      <GameBreadcrumbs game={game} system={game.system} />
      <PlayableHeader
        badgeUrl={game.badgeUrl}
        systemIconUrl={game.system.iconUrl}
        systemLabel={game.system.name}
        title={game.title}
      >
        <div className="flex flex-wrap gap-1 xl:max-w-[610px]">
          <PrimaryMetadataChip
            Icon={LuSettings}
            hubLabel="Developer"
            hubAltLabels={['Hacker']}
            hubs={hubs}
            metadataValue={game.developer}
            visibleLabel={t('Developer')}
          />

          <PrimaryMetadataChip
            Icon={LuPackage}
            hubLabel="Publisher"
            hubAltLabels={['Hacks']}
            hubs={hubs}
            metadataValue={game.publisher}
            visibleLabel={t('Publisher')}
          />

          <PrimaryMetadataChip
            Icon={LuTags}
            hubLabel="Genre"
            hubAltLabels={['Subgenre']}
            hubs={hubs}
            metadataValue={game.genre}
            visibleLabel={t('Genre')}
          />

          <ReleasedAtChip game={game} />
        </div>
      </PlayableHeader>

      <div className="mt-2 hidden sm:block">
        <PlayableMainMedia
          imageIngameUrl={game.imageIngameUrl!}
          imageTitleUrl={game.imageTitleUrl!}
        />
      </div>

      <div className="-mx-3 sm:hidden">
        <PlayableMobileMediaCarousel
          imageIngameUrl={game.imageIngameUrl!}
          imageTitleUrl={game.imageTitleUrl!}
        />
      </div>

      <GameAchievementSetsContainer game={game} />
    </div>
  );
};
