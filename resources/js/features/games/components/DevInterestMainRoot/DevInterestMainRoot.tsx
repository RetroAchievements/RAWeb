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
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

export const DevInterestMainRoot: FC = () => {
  const { developers, game } = usePageProps<App.Platform.Data.DeveloperInterestPageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <GameBreadcrumbs
        game={game}
        system={game.system}
        t_currentPageLabel={t('Developer Interest')}
      />
      <GameHeading game={game}>{t('Developer Interest')}</GameHeading>

      {developers.length ? (
        <div className="flex flex-col">
          <p>{t('The following users have added this game to their Want to Develop list:')}</p>

          <BaseTable>
            <BaseTableHeader>
              <BaseTableRow>
                <BaseTableHead>{t('Dev')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {developers.map((developer) => (
                <BaseTableRow key={`developer-${developer.displayName}`}>
                  <BaseTableCell>
                    <UserAvatar {...developer} />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>
        </div>
      ) : (
        <p>{t('No users have added this game to their Want to Develop list.')}</p>
      )}
    </div>
  );
};
