import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { CreateAchievementTicketMainRoot } from '@/features/achievements/components/CreateAchievementTicketMainRoot';

const CreateAchievementTicket: AppPage = () => {
  const { achievement } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Create Ticket - {{achievementTitle}}', { achievementTitle: achievement.title })}
        description={`Create a ticket for the achievement: ${achievement.title}`}
      />

      <AppLayout.Main>
        <CreateAchievementTicketMainRoot />
      </AppLayout.Main>
    </>
  );
};

CreateAchievementTicket.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default CreateAchievementTicket;
