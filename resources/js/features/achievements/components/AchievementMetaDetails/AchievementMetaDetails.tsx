import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { UserAvatar } from '@/common/components/UserAvatar';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementMetaDetails: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { formatDate } = useFormatDate();

  return (
    <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <BaseTable className="overflow-hidden rounded-lg text-2xs">
        <BaseTableBody>
          <BaseTableRow>
            <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
              {t('Created by')}
            </BaseTableHead>

            <BaseTableCell>
              <UserAvatar {...achievement.developer!} showImage={false} />
            </BaseTableCell>
          </BaseTableRow>

          {achievement.activeMaintainer ? (
            <BaseTableRow>
              <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
                {t('Maintained by')}
              </BaseTableHead>

              <BaseTableCell>
                <UserAvatar {...achievement.activeMaintainer} showImage={false} />
              </BaseTableCell>
            </BaseTableRow>
          ) : null}

          <BaseTableRow>
            <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
              {t('Created')}
            </BaseTableHead>

            <BaseTableCell>{formatDate(achievement.createdAt!, 'll')}</BaseTableCell>
          </BaseTableRow>

          <BaseTableRow>
            <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
              {t('Last modified')}
            </BaseTableHead>

            <BaseTableCell>{formatDate(achievement.modifiedAt!, 'll')}</BaseTableCell>
          </BaseTableRow>
        </BaseTableBody>
      </BaseTable>
    </div>
  );
};
