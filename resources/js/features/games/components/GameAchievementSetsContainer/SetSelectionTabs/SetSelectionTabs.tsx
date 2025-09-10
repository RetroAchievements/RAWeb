import { useAtomValue } from 'jotai';
import { type FC } from 'react';
import { route } from 'ziggy-js';

import { BaseTooltip, BaseTooltipTrigger } from '@/common/components/+vendor/BaseTooltip';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

import { GameAchievementSetTooltipContent } from '../../GameAchievementSetTooltipContent';
import { useTabIndicator } from './useTabIndicator';

interface SetSelectionTabsProps {
  activeTab: number | null;
}

export const SetSelectionTabs: FC<SetSelectionTabsProps> = ({ activeTab }) => {
  const { game, selectableGameAchievementSets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const currentListView = useAtomValue(currentListViewAtom);

  const initialActiveIndex = activeTab
    ? selectableGameAchievementSets.findIndex((gas) => gas.achievementSet.id === activeTab)
    : 0;

  const { activeIndex, indicatorStyles, isAnimationReady, setActiveIndex, tabRefs } =
    useTabIndicator(initialActiveIndex);

  if (!selectableGameAchievementSets.length) {
    return null;
  }

  return (
    <div className="relative">
      {/* Active Tab Indicator */}
      <div
        className={cn(
          'absolute bottom-[-6px] left-0 h-[2px] bg-neutral-300 will-change-transform light:bg-neutral-800',
          isAnimationReady ? 'transition-all duration-200 ease-out' : null,
        )}
        style={indicatorStyles}
      />

      {/* Tabs */}
      <div className="relative flex items-center space-x-[6px]">
        {selectableGameAchievementSets.map((gas, index) => (
          <BaseTooltip key={gas.id}>
            <BaseTooltipTrigger>
              <InertiaLink
                href={route('game2.show', {
                  game: game.id,
                  set: gas.type === 'core' ? undefined : gas.achievementSet.id,
                  view: currentListView === 'leaderboards' ? 'leaderboards' : undefined,
                })}
                prefetch="desktop-hover-only"
                preserveScroll={true}
                onClick={() => {
                  setActiveIndex(index);
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
            </BaseTooltipTrigger>

            <GameAchievementSetTooltipContent gameAchievementSet={gas} />
          </BaseTooltip>
        ))}
      </div>
    </div>
  );
};
