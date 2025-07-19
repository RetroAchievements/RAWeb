import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface AwardEarnersProps {
  paginatedUsers: App.Data.PaginatedData<App.Platform.Data.AwardEarner>;
}

export const AwardEarnersList: FC<AwardEarnersProps> = ({ paginatedUsers }) => {
  const { t } = useTranslation();

  if (!paginatedUsers.items.length) {
    return null;
  }

  return (
    <BaseTable
      containerClassName={cn(
        'overflow-auto rounded-md border border-neutral-700/80 bg-embed',
        'light:border-neutral-300 lg:overflow-visible lg:rounded-sm',
      )}
    >
      <BaseTableHeader>
        <BaseTableRow className="do-not-highlight">
          <BaseTableHead>{t('User')}</BaseTableHead>
          <BaseTableHead>{t('Earned')}</BaseTableHead>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>
        {paginatedUsers.items.map((earner) => (
          <BaseTableRow key={earner.user.displayName}>
            <BaseTableCell>
              <UserAvatar {...earner.user} size={32} />
            </BaseTableCell>

            <BaseTableCell>
              <span>{formatDate(earner.dateEarned, 'lll')}</span>
            </BaseTableCell>
          </BaseTableRow>
        ))}
      </BaseTableBody>
    </BaseTable>
  );
};
