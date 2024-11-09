import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementBreadcrumbs } from '../AchievementBreadcrumbs';
import { AchievementHeading } from '../AchievementHeading';
import { CreateAchievementTicketForm } from './CreateAchievementTicketForm';

export const CreateAchievementTicketMainRoot: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <AchievementBreadcrumbs
        t_currentPageLabel={t('Create Ticket')}
        system={achievement.game?.system}
        game={achievement.game}
        achievement={achievement}
      />
      <AchievementHeading achievement={achievement}>{t('Create Ticket')}</AchievementHeading>

      <div>
        <CreateAchievementTicketForm />
      </div>
    </div>
  );
};
