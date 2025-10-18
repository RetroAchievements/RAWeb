import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseSelectAsync } from '@/common/components/+vendor/BaseSelectAsync';
import { EmptyState } from '@/common/components/EmptyState';
import { GamesListItem } from '@/common/components/GamesListItem';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useUserSearchQuery } from '@/common/hooks/useUserSearchQuery';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameListSection } from '@/features/users/components/GameListSection/GameListSection';

const UserGameChecklist: AppPage = () => {
  const { player, groups } = usePageProps<App.Community.Data.GameChecklistPageProps>();

  const { t } = useTranslation();

  const query = useUserSearchQuery({ initialSearchTerm: player.displayName });

  const handleUserChange = (newUser: string) => {
    router.visit(
      route('user.game-checklist', {
        user: newUser,
        _query: route().queryParams,
      }),
    );
  };

  return (
    <>
      <AppLayout.Main>
        <div>
          <UserBreadcrumbs t_currentPageLabel={t('Game Completion Checklist')} user={player} />
          <UserHeading user={player}>{t('Game Completion Checklist')}</UserHeading>

          <div className="form-grid-container mb-4">
            <label>{t('Examine another user:')}</label>

            <BaseSelectAsync<App.Data.User>
              query={query}
              noResultsMessage={t('No users found.')}
              popoverPlaceholder={t('type a username...')}
              value={''}
              triggerClassName="md:w-[320px] md:max-w-[320px]"
              onChange={handleUserChange}
              width={320}
              placeholder={t('find a user...')}
              getOptionValue={(user) => user.displayName}
              getDisplayValue={(user) => (
                <div className="flex items-center gap-2">
                  <img className="size-6 rounded-sm" src={user.avatarUrl} alt={user.displayName} />
                  <span className="font-medium">{user.displayName}</span>
                </div>
              )}
              renderOption={(user) => (
                <div className="flex items-center gap-2">
                  <img className="size-6 rounded-sm" src={user.avatarUrl} alt={user.displayName} />
                  <span className="font-medium">{user.displayName}</span>
                </div>
              )}
            />
          </div>

          {groups.length > 0 ? (
            <div className="flex flex-col gap-4">
              {groups.map((group, index) => (
                <GameListSection
                  key={`game-group-${index}`}
                  gameCount={group.games.length}
                  isInitiallyOpened={true}
                  title={group.header}
                >
                  {group.games.map((gameListEntry, gameListEntryIndex) => (
                    <GamesListItem
                      key={`game-${gameListEntry.game.id}`}
                      game={gameListEntry.game}
                      index={gameListEntryIndex}
                      playerGame={gameListEntry.playerGame}
                    />
                  ))}
                </GameListSection>
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

UserGameChecklist.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserGameChecklist;
