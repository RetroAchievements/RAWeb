import type { FC, ReactNode } from 'react';

import { BaseTabs, BaseTabsList, BaseTabsTrigger } from '@/common/components/+vendor/BaseTabs';
import { cn } from '@/common/utils/cn';

import { useAchievementShowTabs } from '../../hooks/useAchievementShowTabs';
import type { TabConfig } from '../../models';

interface AchievementTabsProps {
  tabConfigs: TabConfig[];
  children: ReactNode;
}

export const AchievementTabs: FC<AchievementTabsProps> = ({ tabConfigs, children }) => {
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

  return (
    <BaseTabs value={currentTab} onValueChange={handleValueChange}>
      {/* Mobile: static tabs. */}
      <div className="-mx-2.5 overflow-x-auto md:hidden">
        <BaseTabsList
          className={cn(
            'mb-3 flex min-w-full rounded-none border-b border-neutral-600 py-0',
            'bg-neutral-900 light:bg-neutral-200/40',
          )}
        >
          {tabConfigs.map(({ value, label, mobileLabel }) => (
            <BaseTabsTrigger key={value} value={value} variant="underlined">
              {mobileLabel ?? label}
            </BaseTabsTrigger>
          ))}
        </BaseTabsList>
      </div>

      {/* Desktop: animated tabs with a hover indicator. */}
      <div className="hidden md:block">
        <div className="relative">
          <BaseTabsList
            className={cn(
              'relative mb-3 flex w-auto justify-start gap-1 rounded-none px-0 py-0',
              'bg-transparent light:bg-transparent',
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
                  // eslint-disable-next-line react-compiler/react-compiler -- Standard ref callback pattern.
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

      {children}
    </BaseTabs>
  );
};
