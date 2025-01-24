import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { MessagesCard } from './MessagesCard';

export const MessagesCardList: FC = () => {
  const { paginatedMessageThreads } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  return (
    <div className="flex flex-col gap-3" data-testid="messages-card-list">
      {paginatedMessageThreads.items.map((messageThread) => (
        <MessagesCard key={`card-${messageThread.id}`} messageThread={messageThread} />
      ))}
    </div>
  );
};
