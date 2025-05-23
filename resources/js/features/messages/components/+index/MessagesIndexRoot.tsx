import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSend } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { FullPaginator } from '@/common/components/FullPaginator';
import { InertiaLink } from '@/common/components/InertiaLink';
import { UserHeading } from '@/common/components/UserHeading';
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
    senderUser,
    unreadMessageCount,
  } = usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  if (!auth) {
    return null;
  }

  const isDelegating = auth.user.displayName !== senderUser?.displayName;

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      isDelegating
        ? route('message-thread.user.index', {
            user: senderUser?.displayName,
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
              ? t("{{username}}'s Inbox", { username: senderUser?.displayName })
              : t('Your Inbox')
          }
        />
        <UserHeading user={senderUser ?? auth.user} wrapperClassName="!mb-1">
          {t('Messages Inbox')}
        </UserHeading>

        <p>
          {isDelegating
            ? t('delegatedUnreadMessages', {
                username: senderUser?.displayName,
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
                ? route('message-thread.user.create', { user: senderUser?.displayName })
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
