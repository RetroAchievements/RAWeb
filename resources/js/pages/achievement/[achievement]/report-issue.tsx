import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { ReportIssueMainRoot } from '@/features/achievements/components/ReportIssueMainRoot';

const ReportIssue: AppPage = () => {
  const { achievement } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Report Issue - :achievementTitle', { achievementTitle: achievement.title })}>
        <meta
          name="description"
          content={`Report an issue with the achievement: ${achievement.title}`}
        />
      </Head>

      <AppLayout.Main>
        <ReportIssueMainRoot />
      </AppLayout.Main>
    </>
  );
};

ReportIssue.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default ReportIssue;
