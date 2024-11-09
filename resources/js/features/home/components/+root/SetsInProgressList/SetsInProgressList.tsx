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
import type { AvatarSize } from '@/common/models';
import { ClaimSetType } from '@/common/utils/generatedAppConstants';

import { ClaimMobileBlock } from '../../ClaimMobileBlock/ClaimMobileBlock';
import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';

export const SetsInProgressList: FC = () => {
  const { newClaims } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <HomeHeading>{t('Latest Sets in Progress')}</HomeHeading>

      {!newClaims?.length ? (
        <div className="rounded bg-embed">
          <EmptyState>{t("Couldn't find any sets in progress.")}</EmptyState>
        </div>
      ) : null}

      {newClaims?.length ? (
        <>
          <div className="flex flex-col gap-y-1 sm:hidden">
            {newClaims.map((claim) => (
              <ClaimMobileBlock key={`mobile-claim-${claim.id}`} claim={claim} />
            ))}
          </div>

          <BaseTable className="table-highlight hidden sm:table">
            <BaseTableHeader>
              <BaseTableRow className="do-not-highlight">
                <BaseTableHead>{t('Game')}</BaseTableHead>
                <BaseTableHead>{t('Dev')}</BaseTableHead>
                <BaseTableHead>{t('Type')}</BaseTableHead>
                <BaseTableHead>{t('Started')}</BaseTableHead>
              </BaseTableRow>
            </BaseTableHeader>

            <BaseTableBody>
              {newClaims.map((claim) => (
                <BaseTableRow key={`claim-${claim.id}`}>
                  <BaseTableCell>
                    <MultilineGameAvatar {...claim.game} />
                  </BaseTableCell>

                  <BaseTableCell>
                    <UserAvatar {...claim.users[0]} size={36 as AvatarSize} />
                  </BaseTableCell>
                  <BaseTableCell>
                    {claim.setType === ClaimSetType.NewSet && t('New')}
                    {claim.setType === ClaimSetType.Revision && t('Revision')}
                  </BaseTableCell>
                  <BaseTableCell className="smalldate">
                    <DiffTimestamp at={claim.created} />
                  </BaseTableCell>
                </BaseTableRow>
              ))}
            </BaseTableBody>
          </BaseTable>

          <SeeMoreLink href={route('claims.active')} />
        </>
      ) : null}
    </div>
  );
};
