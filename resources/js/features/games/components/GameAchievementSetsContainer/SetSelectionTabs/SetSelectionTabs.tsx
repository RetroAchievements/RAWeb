import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { route } from 'ziggy-js';

import { BaseHoverCard, BaseHoverCardTrigger } from '@/common/components/+vendor/BaseHoverCard';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

import { GameAchievementSetHoverCardContent } from '../../GameAchievementSetHoverCardContent';
import { useHoverCardClickSuppression } from './useHoverCardClickSuppression';
import { useTabIndicator } from './useTabIndicator';

interface SetSelectionTabsProps {
  activeTab: number | null;
}

export const SetSelectionTabs: FC<SetSelectionTabsProps> = ({ activeTab }) => {
  const { game, selectableGameAchievementSets, ziggy } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const currentListView = useAtomValue(currentListViewAtom);

  const initialActiveIndex = activeTab
    ? selectableGameAchievementSets.findIndex((gas) => gas.achievementSet.id === activeTab)
    : 0;

  const { activeIndex, indicatorStyles, isAnimationReady, setActiveIndex, tabRefs } =
    useTabIndicator(initialActiveIndex);

  const { handleHoverCardOpenChange, handlePointerLeave, handleTabClick, openHoverCard } =
    useHoverCardClickSuppression({
      onTabChange: setActiveIndex,
    });

  if (!selectableGameAchievementSets.length) {
    return null;
  }

  return (
    <div className="relative">
      {/* Active Tab Indicator */}
      <div
        data-testid="tab-indicator"
        className={cn(
          'absolute bottom-[-6px] left-0 h-[2px] bg-neutral-300 will-change-transform light:bg-neutral-800',
          isAnimationReady ? 'transition-all duration-200 ease-out' : null,
        )}
        style={indicatorStyles}
      />

      {/* Tabs */}
      <div className="relative flex items-center space-x-[6px]">
        {selectableGameAchievementSets.map((gas, index) => (
          <BaseHoverCard
            key={gas.id}
            openDelay={300}
            closeDelay={100}
            open={ziggy.device === 'mobile' ? false : openHoverCard === index}
            onOpenChange={(isOpen) => {
              handleHoverCardOpenChange(index, isOpen);
            }}
          >
            <BaseHoverCardTrigger asChild>
              <InertiaLink
                href={route('game2.show', {
                  game: game.id,
                  set: gas.type === 'core' ? undefined : gas.achievementSet.id,
                  view: currentListView === 'leaderboards' ? 'leaderboards' : undefined,
                })}
                prefetch="desktop-hover-only"
                preserveScroll={true}
                preserveState={true}
                onClick={() => {
                  handleTabClick(index);
                }}
                onPointerLeave={() => {
                  handlePointerLeave(index);
                }}
              >
                <div
                  ref={(el) => {
                    tabRefs.current[index] = el;
                  }}
                  className={cn(
                    'flex h-[30px] cursor-pointer items-center px-2 pb-[18px] pt-4 duration-300',
                    'lg:active:translate-y-px',
                    index === activeIndex
                      ? 'text-white light:text-[#0e0e10]'
                      : 'text-[#ffffff99] light:text-[#0e0f1199]',
                  )}
                >
                  <img
                    src={gas.achievementSet.imageAssetPathUrl}
                    alt={gas.title ?? BASE_SET_LABEL}
                    className="size-8 select-none rounded-sm"
                  />
                </div>
              </InertiaLink>
            </BaseHoverCardTrigger>

            <GameAchievementSetHoverCardContent gameAchievementSet={gas} />
          </BaseHoverCard>
        ))}
      </div>
    </div>
  );
};
