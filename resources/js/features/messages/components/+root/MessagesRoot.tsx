import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { FullPaginator } from '@/common/components/FullPaginator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { MessagesCardList } from '../MessagesCardList';
import { MessagesTable } from '../MessagesTable';

export const MessagesRoot: FC = memo(() => {
  const { paginatedMessageThreads, unreadMessageCount } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-4">
      <div>
        <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">{t('Messages')}</h1>
        <p>
          {t('unreadMessages', {
            unreadCount: unreadMessageCount,
            threadCount: paginatedMessageThreads.total,
          })}
        </p>
      </div>

      <div className="flex w-full flex-col items-end justify-between gap-2 sm:flex-row sm:items-center">
        <a href={route('message.create')} className={baseButtonVariants({ size: 'sm' })}>
          {t('New Message')}
        </a>

        <FullPaginator paginatedData={paginatedMessageThreads} />
      </div>

      <div className="sm:hidden">
        <MessagesCardList />
      </div>

      <div className="hidden sm:block">
        <MessagesTable />
      </div>

      <div className="flex w-full justify-center sm:justify-end">
        <FullPaginator paginatedData={paginatedMessageThreads} />
      </div>
    </div>
  );
});
