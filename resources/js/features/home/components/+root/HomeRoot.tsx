import { type FC, memo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ActivePlayers } from './ActivePlayers';
import { CurrentlyOnline } from './CurrentlyOnline';
import { FrontPageNews } from './FrontPageNews';
import { GuestWelcomeCta } from './GuestWelcomeCta';
import { NewSetsList } from './NewSetsList';
import { NewUserCta } from './NewUserCta';
import { RecentForumPosts } from './RecentForumPosts';
import { SetsInProgressList } from './SetsInProgressList';
import { TrendingRightNow } from './TrendingRightNow';
import { UserCurrentGame } from './UserCurrentGame';

export const HomeRoot: FC = memo(() => {
  const { auth, userCurrentGame, userCurrentGameMinutesAgo } =
    usePageProps<App.Http.Data.HomePageProps>();

  return (
    <div className="flex flex-col gap-6">
      {!auth?.user ? <GuestWelcomeCta /> : null}

      {userCurrentGame && userCurrentGameMinutesAgo !== null ? <UserCurrentGame /> : null}

      {auth?.user?.isNew ? <NewUserCta /> : null}

      <FrontPageNews />

      <div className="-mt-4">
        <NewSetsList />
      </div>

      <ActivePlayers />

      <div className="mb-4">
        <TrendingRightNow />
      </div>

      <CurrentlyOnline />
      <SetsInProgressList />
      <RecentForumPosts />
    </div>
  );
});
