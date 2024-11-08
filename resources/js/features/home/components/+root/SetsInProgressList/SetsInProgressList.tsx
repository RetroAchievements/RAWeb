import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { MultilineGameAvatar } from '@/common/components/MultilineGameAvatar';
import { UserAvatar } from '@/common/components/UserAvatar';
import type { AvatarSize } from '@/common/models';

import { ClaimMobileBlock } from '../../ClaimMobileBlock/ClaimMobileBlock';
import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';

export const SetsInProgressList: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div>
      <HomeHeading>{t('Latest Sets in Progress')}</HomeHeading>

      <div className="flex flex-col gap-y-1 sm:hidden">
        <ClaimMobileBlock game={mockGame} user={mockUser} />
        <ClaimMobileBlock game={mockGame} user={mockUser} />
        <ClaimMobileBlock game={mockGame} user={mockUser} />
        <ClaimMobileBlock game={mockGame} user={mockUser} />
        <ClaimMobileBlock game={mockGame} user={mockUser} />
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
          <SampleRow />
          <SampleRow />
          <SampleRow />
          <SampleRow />
          <SampleRow />
        </BaseTableBody>
      </BaseTable>

      <SeeMoreLink href={route('claims.active')} />
    </div>
  );
};

const SampleRow: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <BaseTableRow>
      <BaseTableCell>
        <MultilineGameAvatar {...mockGame} />
      </BaseTableCell>
      <BaseTableCell>
        <UserAvatar {...mockUser} size={36 as AvatarSize} />
      </BaseTableCell>
      <BaseTableCell>{t('New')}</BaseTableCell>
      <BaseTableCell className="smalldate">{'2 hours ago'}</BaseTableCell>
    </BaseTableRow>
  );
};

const mockUser: App.Data.User = {
  avatarUrl: 'http://media.retroachievements.org/UserPic/voiceofautumn.png',
  displayName: 'voiceofautumn',
  isMuted: false,
  mutedUntil: null,
};

const mockGame: App.Platform.Data.Game = {
  id: 13776,
  title: 'Football Frenzy',
  badgeUrl: 'http://media.retroachievements.org/Images/103029.png',
  system: {
    id: 27,
    name: 'Arcade',
    iconUrl: 'http://localhost:64000/assets/images/system/arc.png',
    nameShort: 'ARC',
  },
};
