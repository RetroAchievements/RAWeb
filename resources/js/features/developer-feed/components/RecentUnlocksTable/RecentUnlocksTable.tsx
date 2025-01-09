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
import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { EmptyState } from '@/common/components/EmptyState';
import { MultilineGameAvatar } from '@/common/components/MultilineGameAvatar';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

interface RecentUnlocksTableProps {
  recentUnlocks: App.Community.Data.RecentUnlock[];
}

export const RecentUnlocksTable: FC<RecentUnlocksTableProps> = ({ recentUnlocks }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col">
      <h2 className="border-b-0 text-xl font-semibold">{t('Recent Unlocks')}</h2>

      <div className="h-[500px] max-h-[500px] overflow-auto rounded border border-neutral-800 bg-embed light:border-neutral-300">
        {recentUnlocks.length ? (
          <BaseTable>
            <BaseTableHeader className="sticky top-0 z-10 bg-embed">
              <BaseTableRow className="do-not-highlight">
                <BaseTableHead>{t('Achievement')}</BaseTableHead>
                <BaseTableHead>{t('Game')}</BaseTableHead>
                <BaseTableHead>{t('User')}</BaseTableHead>
                <BaseTableHead>{t('Unlocked')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {recentUnlocks.map((recentUnlock) => (
                <BaseTableRow
                  key={`recentUnlock-${recentUnlock.achievement.id}-${recentUnlock.user.displayName}`}
                >
                  <BaseTableCell>
                    <div className="flex max-w-fit items-center gap-1">
                      <AchievementAvatar
                        {...recentUnlock.achievement}
                        showHardcoreUnlockBorder={recentUnlock.isHardcore}
                      />

                      <span className="text-neutral-500 light:text-neutral-700">
                        {!recentUnlock.isHardcore ? '(softcore)' : null}
                      </span>
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      <MultilineGameAvatar {...recentUnlock.game} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      <UserAvatar {...recentUnlock.user} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <DiffTimestamp
                      asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                      at={recentUnlock.unlockedAt}
                      className="text-2xs text-neutral-400 light:text-neutral-700"
                    />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>
        ) : (
          <EmptyState>{t("Couldn't find any recent achievement unlocks.")}</EmptyState>
        )}
      </div>
    </div>
  );
};
