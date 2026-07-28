import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ChangeEmailAddressSectionCard } from '../ChangeEmailAddressSectionCard';
import { ChangePasswordSectionCard } from '../ChangePasswordSectionCard';
import { ChangeUsernameSectionCard } from '../ChangeUsernameSectionCard';
import { DeleteAccountSectionCard } from '../DeleteAccountSectionCard';
import { ResetEntireAccountSectionCard } from '../ResetEntireAccountSectionCard';

export const AccountTabPanel: FC = () => {
  const { auth, can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  return (
    <div className="flex flex-col gap-4">
      {!auth?.user.isMuted && auth?.user.isEmailVerified ? <ChangeUsernameSectionCard /> : null}

      <ChangeEmailAddressSectionCard />
      <ChangePasswordSectionCard />

      <div className="flex flex-col gap-4 border-t border-neutral-700 pt-6 light:border-neutral-300">
        {can.resetEntireAccount ? <ResetEntireAccountSectionCard /> : null}
        <DeleteAccountSectionCard />
      </div>
    </div>
  );
};
