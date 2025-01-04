import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementGroup } from '@/features/achievements/components/AchievementGroup';
import { EmptyState } from '@/common/components/EmptyState';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';

const UserAchievementChecklist: AppPage = () => {
  const { player, groups } = usePageProps<App.Community.Data.AchievementChecklistPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <AppLayout.Main>
        <div>
          <UserBreadcrumbs t_currentPageLabel={t('Achievement Checklist')} user={player} />
          <UserHeading user={player}>
            {t('Achievement Checklist')}
          </UserHeading>

          {groups.length > 0 ? (
            <div>
              {groups.map((group) => (
                <AchievementGroup group={group} showGame={true} />
              ))}
            </div>
          ) : (
            <EmptyState>
              {t('Invalid list')}
            </EmptyState>
          )}
        </div>
      </AppLayout.Main>
    </>
  );
};

UserAchievementChecklist.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserAchievementChecklist;