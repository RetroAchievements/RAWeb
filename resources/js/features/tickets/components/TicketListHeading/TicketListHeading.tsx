import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { AchievementHeading } from '@/common/components/AchievementHeading';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

export const TicketListHeading: FC = () => {
  const { achievement, game, scope, user } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  const headingCopyMap: Record<App.Platform.Enums.TicketListScope, TranslatedString> = {
    all: t('Ticket Manager'),
    game: t('Tickets'),
    achievement: t('Tickets'),
    assignedTo: t('Tickets'),
    reportedBy: t('Tickets Created'),
    awaitingReporter: t('Tickets Awaiting Feedback'),
    resolvedBy: t('Tickets Resolved'),
  };

  const pickedHeadingCopy = headingCopyMap[scope];

  if (game) {
    return (
      <div>
        <GameBreadcrumbs game={game} system={game.system} t_currentPageLabel={pickedHeadingCopy} />

        <GameHeading game={game} wrapperClassName="mb-1!">
          {pickedHeadingCopy}
        </GameHeading>
      </div>
    );
  }

  if (achievement) {
    return (
      <div>
        <AchievementBreadcrumbs
          achievement={achievement}
          game={achievement.game}
          system={achievement.game?.system}
          t_currentPageLabel={pickedHeadingCopy}
        />

        <AchievementHeading achievement={achievement} wrapperClassName="mb-1!">
          {pickedHeadingCopy}
        </AchievementHeading>
      </div>
    );
  }

  if (user) {
    return (
      <div>
        <UserBreadcrumbs user={user} t_currentPageLabel={pickedHeadingCopy} />

        <UserHeading user={user} wrapperClassName="mb-1!">
          {pickedHeadingCopy}
        </UserHeading>
      </div>
    );
  }

  return (
    <div className="mb-1 flex w-full">
      <h1 className="text-h3 w-full sm:text-[2.0em]!">{pickedHeadingCopy}</h1>
    </div>
  );
};
