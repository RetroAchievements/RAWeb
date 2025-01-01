import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { AchievementHeading } from '@/common/components/AchievementHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { CreateAchievementTicketForm } from './CreateAchievementTicketForm';

export const CreateAchievementTicketMainRoot: FC = memo(() => {
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
});
