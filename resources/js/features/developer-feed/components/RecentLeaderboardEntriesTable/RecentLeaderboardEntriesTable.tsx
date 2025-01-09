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
import { EmptyState } from '@/common/components/EmptyState';
import { MultilineGameAvatar } from '@/common/components/MultilineGameAvatar';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

interface RecentLeaderboardEntriesTableProps {
  recentLeaderboardEntries: App.Community.Data.RecentLeaderboardEntry[];
}

export const RecentLeaderboardEntriesTable: FC<RecentLeaderboardEntriesTableProps> = ({
  recentLeaderboardEntries,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col">
      <h2 className="border-b-0 text-xl font-semibold">{t('Recent Leaderboard Entries')}</h2>

      <div className="h-[500px] max-h-[500px] overflow-auto rounded border border-neutral-800 bg-embed light:border-neutral-300">
        {recentLeaderboardEntries.length ? (
          <BaseTable>
            <BaseTableHeader className="sticky top-0 z-10 bg-embed">
              <BaseTableRow className="do-not-highlight">
                <BaseTableHead>{t('Leaderboard')}</BaseTableHead>
                <BaseTableHead>{t('Entry')}</BaseTableHead>
                <BaseTableHead>{t('Game')}</BaseTableHead>
                <BaseTableHead>{t('User')}</BaseTableHead>
                <BaseTableHead>{t('Submitted')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {recentLeaderboardEntries.map((entry) => (
                <BaseTableRow
                  key={`recentLeaderboardEntry-${entry.game.id}-${entry.leaderboardEntry.formattedScore}-${entry.user.displayName}`}
                >
                  <BaseTableCell>
                    <a href={`/leaderboardinfo.php?i=${entry.leaderboard.id}`}>
                      {entry.leaderboard.title}
                    </a>
                  </BaseTableCell>

                  <BaseTableCell>{entry.leaderboardEntry.formattedScore}</BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      <MultilineGameAvatar {...entry.game} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      <UserAvatar {...entry.user} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <DiffTimestamp
                      asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                      at={entry.submittedAt}
                      className="text-2xs text-neutral-400 light:text-neutral-700"
                    />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>
        ) : (
          <EmptyState>{t("Couldn't find any recent leaderboard entries.")}</EmptyState>
        )}
      </div>
    </div>
  );
};
