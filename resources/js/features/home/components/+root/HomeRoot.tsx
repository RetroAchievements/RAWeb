import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ActivePlayers } from './ActivePlayers';
import { CurrentlyOnline } from './CurrentlyOnline';
import { FrontPageNews } from './FrontPageNews/FrontPageNews';
import { GuestWelcomeCta } from './GuestWelcomeCta';
import { NewSetsList } from './NewSetsList';
import { RecentForumPosts } from './RecentForumPosts';
import { SetsInProgressList } from './SetsInProgressList';

export const HomeRoot: FC = () => {
  const { auth } = usePageProps();

  return (
    <div className="flex flex-col gap-6">
      {auth?.user ? null : <GuestWelcomeCta />}

      <FrontPageNews />

      <div className="-mt-4">
        <NewSetsList />
      </div>

      <ActivePlayers />
      <CurrentlyOnline />
      <SetsInProgressList />
      <RecentForumPosts />
    </div>
  );
};
