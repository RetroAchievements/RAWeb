import dayjs from 'dayjs';
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
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { UserAvatar } from '@/common/components/UserAvatar';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementRecentUnlocks: FC = () => {
  const { achievement, recentUnlocks } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const placeholderRowCount = Math.min(achievement.unlocksTotal ?? 0, 50);

  if (placeholderRowCount === 0 || (recentUnlocks !== undefined && !recentUnlocks.length)) {
    return (
      <p className="text-neutral-400 light:text-neutral-600">
        {t('No unlocks found for this achievement.')}
      </p>
    );
  }

  // Empty rows hold the table height while the deferred prop resolves, preventing layout shift.
  const rows =
    recentUnlocks === undefined
      ? Array.from({ length: placeholderRowCount }).map((_, i) => (
          <BaseTableRow key={i}>
            <BaseTableCell>
              <div className="h-8" />
            </BaseTableCell>
            <BaseTableCell />
            <BaseTableCell />
          </BaseTableRow>
        ))
      : recentUnlocks.map((unlock) => (
          <BaseTableRow key={`${unlock.user.displayName}-${unlock.unlockedAt}`}>
            <BaseTableCell>
              <div className="max-w-fit">
                <UserAvatar {...unlock.user} />
              </div>
            </BaseTableCell>

            <BaseTableCell>
              {unlock.isHardcore ? (
                <span className="text-[gold] light:text-yellow-600">{t('Hardcore')}</span>
              ) : null}
            </BaseTableCell>

            <BaseTableCell>
              {dayjs().diff(unlock.unlockedAt, 'hour') < 24 ? (
                <DiffTimestamp
                  at={unlock.unlockedAt}
                  className="text-2xs text-neutral-400 light:text-neutral-700"
                />
              ) : (
                <span className="text-2xs text-neutral-400 light:text-neutral-700">
                  {formatDate(unlock.unlockedAt, 'll')}
                </span>
              )}
            </BaseTableCell>
          </BaseTableRow>
        ));

  return (
    <BaseTable>
      <BaseTableHeader>
        <BaseTableRow className="do-not-highlight">
          <BaseTableHead>{t('Player')}</BaseTableHead>
          <BaseTableHead className="w-28">{t('Mode')}</BaseTableHead>
          <BaseTableHead className="w-40">{t('Unlocked')}</BaseTableHead>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>{rows}</BaseTableBody>
    </BaseTable>
  );
};
