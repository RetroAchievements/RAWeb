import { type FC, useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ChangeEmailAddressSectionCard } from '../ChangeEmailAddressSectionCard';
import { ChangePasswordSectionCard } from '../ChangePasswordSectionCard';
import { DeleteAccountSectionCard } from '../DeleteAccountSectionCard';
import { KeysSectionCard } from '../KeysSectionCard';
import { NotificationsSectionCard } from '../NotificationsSectionCard';
import { PreferencesSectionCard } from '../PreferencesSectionCard';
import { ProfileSectionCard } from '../ProfileSectionCard';
import { ResetGameProgressSectionCard } from '../ResetGameProgressSectionCard';

export const SettingsRoot: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const [currentWebsitePrefs, setCurrentWebsitePrefs] = useState(auth?.user.websitePrefs ?? 0);

  const handleUpdateWebsitePrefs = (newWebsitePrefs: number) => {
    setCurrentWebsitePrefs(newWebsitePrefs);
  };

  return (
    <div className="flex flex-col">
      <h1>Settings</h1>

      <div className="flex flex-col gap-4">
        <ProfileSectionCard />

        {/* Make sure the shared websitePrefs values don't accidentally override each other. */}
        <NotificationsSectionCard
          currentWebsitePrefs={currentWebsitePrefs}
          onUpdateWebsitePrefs={handleUpdateWebsitePrefs}
        />
        <PreferencesSectionCard
          currentWebsitePrefs={currentWebsitePrefs}
          onUpdateWebsitePrefs={handleUpdateWebsitePrefs}
        />

        <KeysSectionCard />
        <ChangePasswordSectionCard />
        <ChangeEmailAddressSectionCard />
        <ResetGameProgressSectionCard />
        <DeleteAccountSectionCard />
      </div>
    </div>
  );
};
