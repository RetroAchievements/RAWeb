import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ChangeEmailAddressSectionCard } from '../ChangeEmailAddressSectionCard';
import { ChangePasswordSectionCard } from '../ChangePasswordSectionCard';
import { DeleteAccountSectionCard } from '../DeleteAccountSectionCard';
import { KeysSectionCard } from '../KeysSectionCard';
import { LocaleSectionCard } from '../LocaleSectionCard';
import { NotificationsSectionCard } from '../NotificationsSectionCard';
import { PreferencesSectionCard } from '../PreferencesSectionCard';
import { ProfileSectionCard } from '../ProfileSectionCard';
import { ResetGameProgressSectionCard } from '../ResetGameProgressSectionCard';

export const SettingsRoot: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  // Make sure the shared websitePrefs values used between NotificationsSectionCard
  // and PreferencesSectionCard don't override each other.
  // TODO can we just have Inertia reload the page data on save?
  const [currentWebsitePrefs, setCurrentWebsitePrefs] = useState(auth?.user.websitePrefs as number);

  const handleUpdateWebsitePrefs = (newWebsitePrefs: number) => {
    setCurrentWebsitePrefs(newWebsitePrefs);
  };

  return (
    <div className="flex flex-col">
      <h1>{t('Settings')}</h1>

      <div className="flex flex-col gap-4">
        <ProfileSectionCard />

        <NotificationsSectionCard
          currentWebsitePrefs={currentWebsitePrefs}
          onUpdateWebsitePrefs={handleUpdateWebsitePrefs}
        />

        <LocaleSectionCard />

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
