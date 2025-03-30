import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { FullPaginator } from '@/common/components/FullPaginator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { MessagesBreadcrumbs } from '../MessagesBreadcrumbs';
import { MessagesCardList } from '../MessagesCardList';
import { MessagesTable } from '../MessagesTable';

export const MessagesIndexRoot: FC = memo(() => {
  const { auth, paginatedMessageThreads, unreadMessageCount } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('message-thread.index', {
        _query: { page: newPageValue },
      }),
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <div>
        {/* TODO in the future, the current user context needs to be passed down as a new prop.
            some users will be able to access team inboxes. */}
        <MessagesBreadcrumbs user={auth!.user} t_currentPageLabel={t('Inbox')} />
        <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">
          {t('Messages Inbox')}
        </h1>

        <p>
          {t('unreadMessages', {
            unreadCount: unreadMessageCount,
            threadCount: paginatedMessageThreads.total,
          })}
        </p>
      </div>

      <div className="flex w-full flex-col items-end justify-between gap-2 sm:flex-row sm:items-center">
        <a href={route('message-thread.create')} className={baseButtonVariants({ size: 'sm' })}>
          {t('New Message')}
        </a>

        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedMessageThreads}
        />
      </div>

      <div className="sm:hidden">
        <MessagesCardList />
      </div>

      <div className="hidden sm:block">
        <MessagesTable />
      </div>

      <div className="flex w-full justify-center sm:justify-end">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedMessageThreads}
        />
      </div>
    </div>
  );
});
