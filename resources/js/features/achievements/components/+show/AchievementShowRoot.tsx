import { type FC, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTabs,
  BaseTabsContent,
  BaseTabsList,
  BaseTabsTrigger,
} from '@/common/components/+vendor/BaseTabs';
import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { useAchievementShowTabs } from '../../hooks/useAchievementShowTabs';
import type { TabConfig } from '../../models';
import { AchievementCommentList } from '../AchievementCommentList';
import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementHero } from '../AchievementHero';
import { AchievementInlineActions } from '../AchievementInlineActions';

export const AchievementShowRoot: FC = () => {
  const { achievement, backingGame, gameAchievementSet } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const {
    currentTab,
    handleValueChange,
    activeIndex,
    setHoveredIndex,
    tabRefs,
    hoverIndicatorRef,
    activeIndicatorStyles,
    isAnimationReady,
  } = useAchievementShowTabs();

  const tabConfigs: TabConfig[] = useMemo(
    () => [
      { value: 'comments', label: t('Comments') },
      {
        value: 'unlocks',
        label: (
          <>
            <span className="md:hidden">{t('Unlocks')}</span>
            <span className="hidden md:block">{t('Recent Unlocks')}</span>
          </>
        ),
      },
      { value: 'changelog', label: t('Changelog') },
    ],
    [t],
  );

  // When the achievement belongs to a subset game, use the backing game for breadcrumbs.
  const breadcrumbGame = backingGame ?? achievement.game;

  return (
    <div>
      <AchievementBreadcrumbs
        t_currentPageLabel={achievement.title as TranslatedString}
        system={breadcrumbGame?.system}
        game={breadcrumbGame}
        gameAchievementSet={gameAchievementSet ?? undefined}
      />

      <div className="flex flex-col gap-3">
        <AchievementHero />

        <div className="md:hidden">
          <AchievementGamePanel />
        </div>

        <div className="flex flex-col gap-6">
          <AchievementInlineActions />

          <BaseTabs value={currentTab} onValueChange={handleValueChange}>
            <div className="-mx-2.5 overflow-x-auto md:mx-0">
              <div className="relative">
                <BaseTabsList
                  className={cn(
                    'relative mb-3 flex w-max min-w-full justify-between rounded-none py-0',
                    'md:w-auto md:min-w-0 md:justify-start md:gap-1 md:px-0',
                    'bg-neutral-900 light:bg-neutral-200/40 md:bg-transparent light:md:bg-transparent',
                  )}
                >
                  <div
                    ref={hoverIndicatorRef}
                    className={cn(
                      'pointer-events-none absolute left-0 top-0 rounded-md opacity-0 will-change-transform',
                      'bg-neutral-700/60 light:bg-neutral-300/60',
                    )}
                  />

                  {tabConfigs.map(({ value, label }, index) => (
                    <BaseTabsTrigger
                      key={value}
                      ref={(el) => {
                        tabRefs.current[index] = el;
                      }}
                      value={value}
                      variant={null}
                      onMouseEnter={() => setHoveredIndex(index)}
                      onMouseLeave={() => setHoveredIndex(null)}
                      className={cn(
                        'relative z-10 h-full whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-medium',
                        'bg-transparent transition-colors duration-200',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-link focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900',

                        activeIndex === index
                          ? 'text-link'
                          : 'text-neutral-500 hover:text-neutral-200 light:text-neutral-700 light:hover:text-neutral-900',
                      )}
                    >
                      {label}
                    </BaseTabsTrigger>
                  ))}
                </BaseTabsList>

                <div
                  data-testid="full-width-separator-line"
                  className="absolute bottom-0 left-0 h-px w-full bg-neutral-700 light:bg-neutral-300"
                  style={{ contain: 'layout' }}
                />

                <div
                  data-testid="tab-indicator"
                  className={cn(
                    'absolute left-0 top-0 h-[2px] will-change-transform',
                    'bg-link',
                    isAnimationReady ? 'transition-all duration-200' : null,
                  )}
                  style={{
                    ...activeIndicatorStyles,
                    transitionTimingFunction: isAnimationReady
                      ? 'cubic-bezier(0.65, 0, 0.35, 1)'
                      : undefined,
                  }}
                />
              </div>
            </div>

            <BaseTabsContent value="comments" className="md:-mt-1">
              <AchievementCommentList />
            </BaseTabsContent>

            <BaseTabsContent value="unlocks">{'AchievementRecentUnlocks'}</BaseTabsContent>

            <BaseTabsContent value="changelog">{'AchievementChangelog'}</BaseTabsContent>
          </BaseTabs>
        </div>
      </div>
    </div>
  );
};
