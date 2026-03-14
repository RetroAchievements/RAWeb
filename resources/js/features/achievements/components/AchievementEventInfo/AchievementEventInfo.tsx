import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { GameAvatar } from '@/common/components/GameAvatar';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementEventInfo: FC = () => {
  const { eventAchievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { formatDate } = useFormatDate();

  if (!eventAchievement) {
    return null;
  }

  const sourceGame = eventAchievement.sourceAchievement?.game;

  return (
    <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <BaseTable className="overflow-hidden rounded-lg text-2xs">
        <BaseTableBody>
          <BaseTableRow>
            <BaseTableHead scope="row" className="h-auto text-right align-middle text-text">
              {t('From')}
            </BaseTableHead>

            <BaseTableCell className="h-[42px]">
              {sourceGame ? <GameAvatar {...sourceGame} size={24} /> : <span>{'–'}</span>}
            </BaseTableCell>
          </BaseTableRow>

          <BaseTableRow>
            <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
              {t('Active')}
            </BaseTableHead>

            <BaseTableCell>
              {eventAchievement.activeFrom && eventAchievement.activeThrough ? (
                <>
                  {formatDate(eventAchievement.activeFrom, 'll')}
                  {' – '}
                  {formatDate(eventAchievement.activeThrough, 'll')}
                </>
              ) : (
                <span>{'–'}</span>
              )}
            </BaseTableCell>
          </BaseTableRow>
        </BaseTableBody>
      </BaseTable>
    </div>
  );
};
