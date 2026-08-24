import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { AchievementHeading } from '@/common/components/AchievementHeading';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

export const TicketListHeading: FC = () => {
  const { achievement, game } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  if (game) {
    return (
      <div>
        <GameBreadcrumbs game={game} system={game.system} t_currentPageLabel={t('Tickets')} />

        <GameHeading game={game} wrapperClassName="mb-1!">
          {t('Tickets')}
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
          t_currentPageLabel={t('Tickets')}
        />

        <AchievementHeading achievement={achievement} wrapperClassName="mb-1!">
          {t('Tickets')}
        </AchievementHeading>
      </div>
    );
  }

  return (
    <div className="mb-1 flex w-full">
      <h1 className="text-h3 w-full sm:text-[2.0em]!">{t('Ticket Manager')}</h1>
    </div>
  );
};
