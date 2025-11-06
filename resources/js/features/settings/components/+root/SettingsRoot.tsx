import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ChangeEmailAddressSectionCard } from '../ChangeEmailAddressSectionCard';
import { ChangePasswordSectionCard } from '../ChangePasswordSectionCard';
import { ChangeUsernameSectionCard } from '../ChangeUsernameSectionCard';
import { DeleteAccountSectionCard } from '../DeleteAccountSectionCard';
import { KeysSectionCard } from '../KeysSectionCard';
import { LocaleSectionCard } from '../LocaleSectionCard';
import { NotificationsSectionCard } from '../NotificationsSectionCard';
import { PreferencesSectionCard } from '../PreferencesSectionCard';
import { ProfileSectionCard } from '../ProfileSectionCard';
import { ResetEntireAccountSectionCard } from '../ResetEntireAccountSectionCard';
import { ResetGameProgressSectionCard } from '../ResetGameProgressSectionCard';

export const SettingsRoot: FC = memo(() => {
  const { auth, can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col">
      <h1>{t('Settings')}</h1>

      <div className="flex flex-col gap-4">
        <ProfileSectionCard />

        <NotificationsSectionCard currentWebsitePrefs={auth?.user.websitePrefs as number} />

        <LocaleSectionCard />

        <PreferencesSectionCard currentWebsitePrefs={auth?.user.websitePrefs as number} />

        <KeysSectionCard />

        {!auth?.user.isMuted && auth?.user.isEmailVerified ? <ChangeUsernameSectionCard /> : null}

        <ChangePasswordSectionCard />

        <ChangeEmailAddressSectionCard />

        <ResetGameProgressSectionCard />

        {can.resetEntireAccount ? <ResetEntireAccountSectionCard /> : null}

        <DeleteAccountSectionCard />
      </div>
    </div>
  );
});
