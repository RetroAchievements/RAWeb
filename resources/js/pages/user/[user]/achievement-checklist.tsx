import { useTranslation } from 'react-i18next';

import { BaseSelectAsync } from '@/common/components/+vendor/BaseSelectAsync';
import { EmptyState } from '@/common/components/EmptyState';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AchievementGroup } from '@/features/achievements/components/AchievementGroup';
import { router } from '@inertiajs/react';

const UserAchievementChecklist: AppPage = () => {
  const { player, groups } = usePageProps<App.Community.Data.AchievementChecklistPageProps>();

  const { t } = useTranslation();
  
  const query = useSearchQuery({ initialSearchTerm: player.displayName });

  const handleUserChange = (newUser: string) => {
    router.visit(
      route('user.achievement-checklist', {
        user: newUser,
        _query: route().queryParams,
      })
    );
  };

  return (
    <>
      <AppLayout.Main>
        <div>
          <UserBreadcrumbs t_currentPageLabel={t('Achievement Checklist')} user={player} />
          <UserHeading user={player}>{t('Achievement Checklist')}</UserHeading>

          <div className="form-grid-container">
            <label>Examine another user:</label>
              
            <BaseSelectAsync<App.Data.User>
              query={query}
              noResultsMessage={t('No users found.')}
              popoverPlaceholder={t('type a username...')}
              value={player.displayName}
              triggerClassName="md:w-[320px] md:max-w-[320px]"
              onChange={handleUserChange}
              width={320}
              placeholder={t('find a user...')}
              getOptionValue={(user) => user.displayName}
              getDisplayValue={(user) => (
                <div className="flex items-center gap-2">
                  <img className="size-6 rounded-sm" src={user.avatarUrl} />
                  <span className="font-medium">{user.displayName}</span>
                </div>
              )}
              renderOption={(user) => (
                <div className="flex items-center gap-2">
                  <img className="size-6 rounded-sm" src={user.avatarUrl} />
                  <span className="font-medium">{user.displayName}</span>
                </div>
              )}
            />
          </div>

          {groups.length > 0 ? (
            <div className="flex flex-col gap-4">
              {groups.map((group, index) => (
                <AchievementGroup group={group} showGame={true} key={`ach-group-${index}`} />
              ))}
            </div>
          ) : (
            <EmptyState>{t('Invalid list')}</EmptyState>
          )}
        </div>
      </AppLayout.Main>
    </>
  );
};

UserAchievementChecklist.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserAchievementChecklist;
