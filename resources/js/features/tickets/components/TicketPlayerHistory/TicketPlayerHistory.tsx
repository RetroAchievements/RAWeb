import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuClock, LuInfo } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';
import { GameTitle } from '@/common/components/GameTitle';
import { UserGameActivityClientBreakdown } from '@/common/components/UserGameActivityClientBreakdown';
import { UserGameActivityTimeline } from '@/common/components/UserGameActivityTimeline';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { TicketCollapsibleSection } from '../TicketCollapsibleSection';

export const TicketPlayerHistory: FC = () => {
  const { hasReporterHistoryAccess, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();

  if (!hasReporterHistoryAccess || !ticket.reporter) {
    return null;
  }

  return <ReporterHistoryPanel reporter={ticket.reporter} />;
};

interface ReporterHistoryPanelProps {
  reporter: App.Data.User;
}

const ReporterHistoryPanel: FC<ReporterHistoryPanelProps> = ({ reporter }) => {
  const { achievement, reporterActivity, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const fullActivityHref = route('user.game.activity.show', {
    user: reporter.displayName,
    game: ticket.game.id,
  });

  return (
    <TicketCollapsibleSection
      Icon={LuClock}
      label={
        <Trans
          i18nKey="{{user}}'s activity for <1>{{game}}</1>"
          values={{ user: reporter.displayName }}
          components={{ 1: <GameTitle {...ticket.game} /> }}
        />
      }
      className={cn(
        'rounded-lg border border-neutral-700 bg-embed',
        'light:border-neutral-200 light:bg-white',
      )}
    >
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
            fullActivityHref={fullActivityHref}
            maxSessionsAroundTicket={3}
            reportedAchievementId={achievement?.id}
          />
        </>
      ) : (
        <BaseSkeleton data-testid="player-history-skeleton" className="h-40 w-full" />
      )}
    </TicketCollapsibleSection>
  );
};
