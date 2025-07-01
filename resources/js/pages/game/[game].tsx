import { useHydrateAtoms } from 'jotai/utils';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameShowMainRoot } from '@/features/games/components/+show';
import { GameShowSidebarRoot } from '@/features/games/components/+show-sidebar';
import {
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { buildGameMetaDescription } from '@/features/games/utils/buildGameMetaDescription';
import type { TranslatedString } from '@/types/i18next';

const GameShow: AppPage<App.Platform.Data.GameShowPageProps> = ({
  game,
  isLockedOnlyFilterEnabled,
  isMissableOnlyFilterEnabled,
}) => {
  // Set initial filter states from page props.
  useHydrateAtoms([
    [isLockedOnlyFilterEnabledAtom, isLockedOnlyFilterEnabled],
    [isMissableOnlyFilterEnabledAtom, isMissableOnlyFilterEnabled],
    //
  ]);

  const title = `${game.title} (${game.system!.name})`;

  return (
    <>
      <SEO
        title={title as TranslatedString}
        description={buildGameMetaDescription(game)}
        ogImage={game!.badgeUrl}
      />

      <AppLayout.Main>
        <GameShowMainRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <GameShowSidebarRoot />
      </AppLayout.Sidebar>
    </>
  );
};

GameShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default GameShow;
