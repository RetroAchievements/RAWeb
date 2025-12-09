import { useSetAtom } from 'jotai';
import { useHydrateAtoms } from 'jotai/utils';
import { useEffect } from 'react';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameShowMainRoot } from '@/features/games/components/+show';
import { GameShowMobileRoot } from '@/features/games/components/+show-mobile';
import { GameShowSidebarRoot } from '@/features/games/components/+show-sidebar';
import { useGameMetaDescription } from '@/features/games/hooks/useGameMetaDescription';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  currentTabAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { getInitialMobileTab } from '@/features/games/utils/getInitialMobileTab';
import type { TranslatedString } from '@/types/i18next';

const GameShow: AppPage = () => {
  const {
    backingGame,
    game,
    initialSort,
    initialView,
    isLockedOnlyFilterEnabled,
    isMissableOnlyFilterEnabled,
    targetAchievementSetId,
    ziggy,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const setCurrentPlayableListSort = useSetAtom(currentPlayableListSortAtom);

  useHydrateAtoms([
    [currentListViewAtom, initialView],
    [currentPlayableListSortAtom, initialSort],
    [currentTabAtom, getInitialMobileTab(ziggy.query?.['tab'] as string | undefined)],
    [isLockedOnlyFilterEnabledAtom, isLockedOnlyFilterEnabled],
    [isMissableOnlyFilterEnabledAtom, isMissableOnlyFilterEnabled],
    //
  ]);

  // Reset the sort order when switching between achievement sets.
  useEffect(() => {
    setCurrentPlayableListSort(initialSort);
  }, [targetAchievementSetId, initialSort, setCurrentPlayableListSort]);

  const { description, noindex } = useGameMetaDescription();

  const title = `${backingGame.title} (${game.system!.name})`;

  return (
    <>
      <SEO
        title={title as TranslatedString}
        description={description}
        ogImage={backingGame!.badgeUrl}
        noindex={noindex}
      />

      {ziggy.device === 'mobile' ? (
        <AppLayout.Main>
          <GameShowMobileRoot />
        </AppLayout.Main>
      ) : (
        <>
          <AppLayout.Main>
            <GameShowMainRoot />
          </AppLayout.Main>

          <AppLayout.Sidebar>
            <GameShowSidebarRoot />
          </AppLayout.Sidebar>
        </>
      )}
    </>
  );
};

GameShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default GameShow;
