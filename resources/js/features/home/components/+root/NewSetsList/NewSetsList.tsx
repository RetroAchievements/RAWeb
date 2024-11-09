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

// TODO shouldn't need to keep using bg-zinc-800 on system chips... needs to be a variant
// TODO revisions
// TODO what if multiple authors
// TODO empty state

export const NewSetsList: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading>{t('Just Released')}</HomeHeading>

      <div className="flex flex-col gap-y-1 sm:hidden">
        <ClaimMobileBlock game={mockGame} user={mockUser} />
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
            <BaseTableHead>{t('Finished')}</BaseTableHead>
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

      <div className="-mt-2.5">
        <SeeMoreLink href={route('claims.completed')} />
      </div>
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
  id: 1,
  displayName: 'Scott',
  avatarUrl: 'http://media.retroachievements.org/UserPic/Scott.png',
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
