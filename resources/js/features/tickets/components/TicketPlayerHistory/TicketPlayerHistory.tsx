import { AnimatePresence, useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuChevronDown, LuClock, LuInfo } from 'react-icons/lu';

import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';
import { GameTitle } from '@/common/components/GameTitle';
import { UserGameActivityClientBreakdown } from '@/common/components/UserGameActivityClientBreakdown';
import { UserGameActivityTimeline } from '@/common/components/UserGameActivityTimeline';
import { useAnimatedCollapse } from '@/common/hooks/useAnimatedCollapse';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

const historyPanelShellClassName = cn(
  'rounded-lg border border-neutral-700 bg-embed',
  'light:border-neutral-200 light:bg-white',
);

export const TicketPlayerHistory: FC = () => {
  const { hasReporterHistoryAccess, hasReporterPlayedGame } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();

  if (!hasReporterHistoryAccess) {
    return null;
  }

  if (!hasReporterPlayedGame) {
    return <NoRecordedActivityNotice />;
  }

  return <ReporterHistoryPanel />;
};

const NoRecordedActivityNotice: FC = () => {
  const { ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();

  return (
    <div className={cn(historyPanelShellClassName, 'flex items-start gap-3 px-3 py-2')}>
      <LuClock className="mt-0.5 size-4 flex-none text-neutral-400" aria-hidden="true" />

      <p>
        <Trans
          i18nKey="{{user}} has no recorded activity for <1>{{game}}</1>."
          values={{ user: ticket.reporter?.displayName }}
          components={{ 1: <GameTitle {...ticket.game} /> }}
        />
      </p>
    </div>
  );
};

const ReporterHistoryPanel: FC = () => {
  const { achievement, reporterActivity, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();
  const [collapseHeight, setCollapseHeight] = useState(0);

  const doesPreferReducedMotion = useReducedMotion();

  const handleExpandedChange = (isExpanding: boolean) => {
    if (!isExpanding) {
      setCollapseHeight(Math.min(contentRef.current!.offsetHeight, window.innerHeight));
    }

    setIsOpen(isExpanding);
  };

  const expandHeight = Math.min(contentHeight, window.innerHeight);
  const expandTransition = doesPreferReducedMotion
    ? { height: { duration: 0 }, opacity: { duration: 0.15 } }
    : { duration: 0.25, ease: [0.23, 1, 0.32, 1] as const };

  return (
    <BaseCollapsible
      open={isOpen}
      onOpenChange={handleExpandedChange}
      className={historyPanelShellClassName}
    >
      <BaseCollapsibleTrigger className="flex w-full items-center gap-3 px-3 py-2 text-left text-link hover:text-link-hover">
        <LuClock className="size-4 flex-none text-neutral-400" aria-hidden="true" />

        <span className="grow">
          {t("{{user}}'s activity for {{gameTitle}}", {
            user: ticket.reporter?.displayName,
            gameTitle: ticket.game.title,
          })}
        </span>

        <LuChevronDown
          className={cn(
            'size-4 flex-none text-neutral-400 transition-transform duration-200',
            isOpen ? 'rotate-180' : 'rotate-0',
          )}
          aria-hidden="true"
        />
      </BaseCollapsibleTrigger>

      <AnimatePresence initial={false} custom={collapseHeight}>
        {isOpen ? (
          <BaseCollapsibleContent forceMount asChild>
            <m.div
              data-testid="player-history-content"
              custom={collapseHeight}
              variants={{
                collapsed: (collapsedHeight: number) => ({
                  height: [collapsedHeight, 0],
                  opacity: 0,
                }),
              }}
              initial={{ height: 0, opacity: 0 }}
              animate={{
                height: expandHeight,
                opacity: 1,
                ...(expandHeight > 0 && { transitionEnd: { height: 'auto' } }),
              }}
              exit="collapsed"
              transition={expandTransition}
              className="overflow-hidden border-t border-neutral-700 light:border-neutral-200"
            >
              <div ref={contentRef} className="flex flex-col gap-4 p-3">
                {reporterActivity ? (
                  <>
                    <UserGameActivityClientBreakdown activity={reporterActivity} />

                    <p className="flex items-start gap-1.5 text-xs text-neutral-400 light:text-neutral-600">
                      <LuInfo className="mt-0.5 size-3.5 flex-none" aria-hidden="true" />

                      {t(
                        'The server records each timestamp when it receives the request. A player with network problems can have an unusual order of unlocks in a short time.',
                      )}
                    </p>

                    <UserGameActivityTimeline
                      activity={reporterActivity}
                      isOnlyShowingAchievementSessions={false}
                      reportedAchievementId={achievement?.id}
                    />
                  </>
                ) : (
                  <BaseSkeleton data-testid="player-history-skeleton" className="h-40 w-full" />
                )}
              </div>
            </m.div>
          </BaseCollapsibleContent>
        ) : null}
      </AnimatePresence>
    </BaseCollapsible>
  );
};
