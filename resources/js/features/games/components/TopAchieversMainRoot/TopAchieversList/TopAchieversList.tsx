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
import { PlayerBadgeIndicator } from '@/common/components/PlayerBadgeIndicator';
import { PlayerBadgeLabel } from '@/common/components/PlayerBadgeLabel';
import { UserAvatar } from '@/common/components/UserAvatar';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { AwardType } from '@/common/utils/generatedAppConstants';
import { formatDate } from '@/common/utils/l10n/formatDate';

export const TopAchieversList: FC = () => {
  const { paginatedUsers } = usePageProps<App.Platform.Data.GameTopAchieversPageProps>();

  const { t } = useTranslation();
  const { formatNumber } = useFormatNumber();

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
          <BaseTableHead>{t('Rank')}</BaseTableHead>
          <BaseTableHead>{t('User')}</BaseTableHead>
          <BaseTableHead>{t('Progress')}</BaseTableHead>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>
        {paginatedUsers.items.map((achiever) => (
          <BaseTableRow key={achiever.user.displayName}>
            <BaseTableCell>{formatNumber(achiever.rank)}</BaseTableCell>

            <BaseTableCell>
              <div className="max-w-fit">
                <UserAvatar {...achiever.user} size={32} />
              </div>
            </BaseTableCell>

            <BaseTableCell>
              {achiever.badge ? (
                <div>
                  {achiever.badge.awardType === AwardType.Mastery && (
                    <span>{formatDate(achiever.badge.awardDate, 'lll')}</span>
                  )}

                  <div className="flex items-center gap-1">
                    {achiever.badge.awardType === AwardType.GameBeaten && (
                      <span>
                        {formatNumber(achiever.score)}
                        <span className="text-muted">{' - '}</span>
                      </span>
                    )}

                    <PlayerBadgeIndicator playerBadge={achiever.badge} className="mt-px" />
                    <PlayerBadgeLabel playerBadge={achiever.badge} variant="muted-group" />
                  </div>
                </div>
              ) : (
                <span>{formatNumber(achiever.score)}</span>
              )}
            </BaseTableCell>
          </BaseTableRow>
        ))}
      </BaseTableBody>
    </BaseTable>
  );
};
