import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSend } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { FullPaginator } from '@/common/components/FullPaginator';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { ChangeInboxButton } from '../ChangeInboxButton';
import { MessagesBreadcrumbs } from '../MessagesBreadcrumbs';
import { MessagesCardList } from '../MessagesCardList';
import { MessagesTable } from '../MessagesTable';

export const MessagesIndexRoot: FC = memo(() => {
  const {
    auth,
    paginatedMessageThreads,
    selectableInboxDisplayNames,
    senderUserDisplayName,
    unreadMessageCount,
  } = usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  if (!auth) {
    return null;
  }

  const isDelegating = auth.user.displayName !== senderUserDisplayName;

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      isDelegating
        ? route('message-thread.user.index', {
            user: senderUserDisplayName,
            _query: { page: newPageValue },
          })
        : route('message-thread.index', {
            _query: { page: newPageValue },
          }),
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <div>
        <MessagesBreadcrumbs
          shouldShowInboxLinkCrumb={false}
          t_currentPageLabel={
            isDelegating
              ? t("{{username}}'s Inbox", { username: senderUserDisplayName })
              : t('Your Inbox')
          }
        />
        <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">
          {t('Messages Inbox')}
        </h1>

        <p>
          {isDelegating
            ? t('delegatedUnreadMessages', {
                username: senderUserDisplayName,
                unreadCount: unreadMessageCount,
                threadCount: paginatedMessageThreads.total,
              })
            : t('unreadMessages', {
                unreadCount: unreadMessageCount,
                threadCount: paginatedMessageThreads.total,
              })}
        </p>
      </div>

      <div className="flex w-full flex-col items-end justify-between gap-2 sm:flex-row sm:items-center">
        <div className="flex items-center gap-2">
          <InertiaLink
            href={
              isDelegating
                ? route('message-thread.user.create', { user: senderUserDisplayName })
                : route('message-thread.create')
            }
            className={baseButtonVariants({ size: 'sm' })}
          >
            <LuSend className="mr-1.5 size-4" />
            {t('New Message')}
          </InertiaLink>

          {selectableInboxDisplayNames.length > 1 ? <ChangeInboxButton /> : null}
        </div>

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
