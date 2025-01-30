import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { usePageProps } from '@/common/hooks/usePageProps';

import { MessagesTableRow } from './MessagesTableRow';

export const MessagesTable: FC = () => {
  const { paginatedMessageThreads } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  return (
    <BaseTable data-testid="messages-table">
      <BaseTableHeader>
        <BaseTableRow className="do-not-highlight">
          <BaseTableHead>{t('Subject')}</BaseTableHead>
          <BaseTableHead>{t('With')}</BaseTableHead>
          <BaseTableHead className="text-right">{t('Message Count')}</BaseTableHead>
          <BaseTableHead className="text-right">{t('Last Message')}</BaseTableHead>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>
        {paginatedMessageThreads.items.map((messageThread) => (
          <MessagesTableRow key={messageThread.id} messageThread={messageThread} />
        ))}
      </BaseTableBody>
    </BaseTable>
  );
};
