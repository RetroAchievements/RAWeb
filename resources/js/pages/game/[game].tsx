import { useSetAtom } from 'jotai';
import { useHydrateAtoms } from 'jotai/utils';
import { useEffect } from 'react';

import { SEO } from '@/common/components/SEO';
import { SEOPreloadImage } from '@/common/components/SEOPreloadImage';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameShowMainRoot } from '@/features/games/components/+show';
import { GameShowMobileRoot } from '@/features/games/components/+show-mobile';
import { GameShowSidebarRoot } from '@/features/games/components/+show-sidebar';
import { GameDesktopBanner } from '@/features/games/components/GameDesktopBanner';
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
    banner,
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
  // TODO this probably shouldn't live at the page component level
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

      {/* TODO when banners get used on other pages, build a SEOPreloadBanner component */}
      {ziggy.device === 'mobile' && (banner?.mobileSmAvif || game.imageIngameUrl) ? (
        <SEOPreloadImage
          src={banner?.mobileSmAvif ?? (game.imageIngameUrl as string)}
          type={banner?.mobileSmAvif ? 'image/avif' : 'image/png'}
          media="(max-width: 767px)"
        />
      ) : null}
      {ziggy.device === 'desktop' && banner?.desktopMdAvif ? (
        <SEOPreloadImage
          src={banner.desktopMdAvif}
          media="(min-width: 768px)"
          imageSrcSet={[
            banner.desktopMdAvif && `${banner.desktopMdAvif} 1024w`,
            banner.desktopLgAvif && `${banner.desktopLgAvif} 1280w`,
            banner.desktopXlAvif && `${banner.desktopXlAvif} 1920w`,
          ]
            .filter(Boolean)
            .join(', ')}
          imageSizes="100vw"
          type="image/avif"
        />
      ) : null}

      {ziggy.device === 'mobile' ? (
        <AppLayout.Main>
          <GameShowMobileRoot />
        </AppLayout.Main>
      ) : (
        <>
          <AppLayout.Banner className="md:-mb-[30px]">
            <GameDesktopBanner banner={banner} />
          </AppLayout.Banner>

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
