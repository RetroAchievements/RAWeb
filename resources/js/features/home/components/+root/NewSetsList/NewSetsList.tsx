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
import { ClaimSetType } from '@/common/utils/generatedAppConstants';

import { ClaimMobileBlock } from '../../ClaimMobileBlock';
import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';

// TODO shouldn't need to keep using bg-zinc-800 on system chips... needs to be a variant

export const NewSetsList: FC = () => {
  const { completedClaims } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading>{t('Just Released')}</HomeHeading>

      {!completedClaims?.length ? (
        <div className="rounded bg-embed">
          <EmptyState>{t("Couldn't find any completed claims.")}</EmptyState>
        </div>
      ) : null}

      {completedClaims?.length ? (
        <>
          <div className="flex flex-col gap-y-1 sm:hidden">
            {completedClaims.map((claim) => (
              <ClaimMobileBlock
                key={`mobile-claim-${claim.id}`}
                claim={claim}
                variant="completed"
              />
            ))}
          </div>

          <BaseTable className="table-highlight hidden sm:table">
            <BaseTableHeader>
              <BaseTableRow className="do-not-highlight">
                <BaseTableHead>{t('Game')}</BaseTableHead>
                <BaseTableHead>{t('Dev')}</BaseTableHead>
                <BaseTableHead>{t('Type')}</BaseTableHead>
                <BaseTableHead>{t('Finished')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {completedClaims.map((claim) => (
                <BaseTableRow key={`claim-${claim.id}`}>
                  <BaseTableCell>
                    <MultilineGameAvatar {...claim.game} />
                  </BaseTableCell>

                  <BaseTableCell>
                    <UserAvatar {...claim.users[0]} size={32} />
                  </BaseTableCell>
                  <BaseTableCell>
                    {claim.setType === ClaimSetType.NewSet && t('New')}
                    {claim.setType === ClaimSetType.Revision && t('Revision')}
                  </BaseTableCell>
                  <BaseTableCell className="smalldate">
                    <DiffTimestamp at={claim.finished} />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>

          <div className="-mt-2.5">
            <SeeMoreLink href={route('claims.completed')} />
          </div>
        </>
      ) : null}
    </div>
  );
};
