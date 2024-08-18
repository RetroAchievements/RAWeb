import type { FC } from 'react';

import { ChangeEmailAddressSectionCard } from '../ChangeEmailAddressSectionCard';
import { ChangePasswordSectionCard } from '../ChangePasswordSectionCard';
import { DeleteAccountSectionCard } from '../DeleteAccountSectionCard';
import { KeysSectionCard } from '../KeysSectionCard';
import { NotificationsSectionCard } from '../NotificationsSectionCard';
import { PreferencesSectionCard } from '../PreferencesSectionCard';
import { ProfileSectionCard } from '../ProfileSectionCard';
import { ResetGameProgressSectionCard } from '../ResetGameProgressSectionCard';

export const SettingsRoot: FC = () => {
  return (
    <div className="flex flex-col">
      <h1>Settings</h1>

      <div className="flex flex-col gap-4">
        <ProfileSectionCard />
        <NotificationsSectionCard />
        <PreferencesSectionCard />
        <KeysSectionCard />
        <ChangePasswordSectionCard />
        <ChangeEmailAddressSectionCard />
        <ResetGameProgressSectionCard />
        <DeleteAccountSectionCard />
      </div>
    </div>
  );
};
