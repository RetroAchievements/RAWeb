import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { ReportIssueMainRoot } from '@/features/achievements/components/ReportIssueMainRoot';

const ReportIssue: AppPage = () => {
  const { achievement } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Report Issue - {{achievementTitle}}', { achievementTitle: achievement.title })}
        description={`Report an issue with the achievement: ${achievement.title}`}
      />

      <AppLayout.Main>
        <ReportIssueMainRoot />
      </AppLayout.Main>
    </>
  );
};

ReportIssue.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default ReportIssue;
