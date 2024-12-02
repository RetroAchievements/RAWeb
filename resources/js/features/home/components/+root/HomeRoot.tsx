import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ActivePlayers } from './ActivePlayers';
import { CurrentlyOnline } from './CurrentlyOnline';
import { FrontPageNews } from './FrontPageNews';
import { GuestWelcomeCta } from './GuestWelcomeCta';
import { NewSetsList } from './NewSetsList';
import { RecentForumPosts } from './RecentForumPosts';
import { SetsInProgressList } from './SetsInProgressList';
import { TrendingRightNow } from './TrendingRightNow';

export const HomeRoot: FC = () => {
  const { auth } = usePageProps<App.Http.Data.HomePageProps>();

  return (
    <div className="flex flex-col gap-6">
      {auth?.user ? null : <GuestWelcomeCta />}

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
};
