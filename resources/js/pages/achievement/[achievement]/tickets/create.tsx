import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { CreateAchievementTicketMainRoot } from '@/features/achievements/components/CreateAchievementTicketMainRoot';

const CreateAchievementTicket: AppPage = () => {
  const { achievement } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <Head
        title={t('Create Ticket - {{achievementTitle}}', { achievementTitle: achievement.title })}
      >
        <meta
          name="description"
          content={`Create a ticket for the achievement: ${achievement.title}`}
        />
      </Head>

      <AppLayout.Main>
        <CreateAchievementTicketMainRoot />
      </AppLayout.Main>
    </>
  );
};

CreateAchievementTicket.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default CreateAchievementTicket;
