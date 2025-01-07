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
import type { TranslatedString } from '@/types/i18next';

interface RecentAwardsTableProps {
  recentPlayerBadges: App.Community.Data.RecentPlayerBadge[];
}

export const RecentAwardsTable: FC<RecentAwardsTableProps> = ({ recentPlayerBadges }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const { translateAwardLabel } = useTranslateAwardLabel();

  return (
    <div className="flex flex-col">
      <h2 className="border-b-0 text-xl font-semibold">{t('Recent Awards')}</h2>

      <div className="h-[500px] max-h-[500px] overflow-auto rounded border border-neutral-800 bg-embed light:border-neutral-300">
        {recentPlayerBadges.length ? (
          <BaseTable>
            <BaseTableHeader className="sticky top-0 z-10 bg-embed">
              <BaseTableRow className="do-not-highlight">
                <BaseTableHead>{t('Game')}</BaseTableHead>
                <BaseTableHead>{t('Award Kind')}</BaseTableHead>
                <BaseTableHead>{t('User')}</BaseTableHead>
                <BaseTableHead>{t('Earned')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {recentPlayerBadges.map((recentPlayerBadge) => (
                <BaseTableRow
                  key={`recentAward-${recentPlayerBadge.user.displayName}-${recentPlayerBadge.game.title}`}
                >
                  <BaseTableCell>
                    <div className="max-w-fit">
                      <MultilineGameAvatar {...recentPlayerBadge.game} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      {translateAwardLabel(recentPlayerBadge.awardType as UnprocessedAwardLabel)}
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <div className="max-w-fit">
                      <UserAvatar {...recentPlayerBadge.user} />
                    </div>
                  </BaseTableCell>

                  <BaseTableCell>
                    <DiffTimestamp
                      asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                      at={recentPlayerBadge.earnedAt}
                      className="text-2xs text-neutral-400 light:text-neutral-700"
                    />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>
        ) : (
          <EmptyState>{t("Couldn't find any recent awards.")}</EmptyState>
        )}
      </div>
    </div>
  );
};

type UnprocessedAwardLabel = 'beaten-softcore' | 'beaten-hardcore' | 'completed' | 'mastered';

function useTranslateAwardLabel() {
  const { t } = useTranslation();

  const translateAwardLabel = (unprocessedLabel: UnprocessedAwardLabel): TranslatedString => {
    switch (unprocessedLabel) {
      case 'beaten-hardcore':
        return t('Beaten');

      case 'completed':
        return t('Completed');

      case 'mastered':
        return t('Mastered');

      default:
        return t('Beaten (softcore)');
    }
  };

  return { translateAwardLabel };
}
