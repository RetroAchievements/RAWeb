import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AvatarSection } from '../AvatarSection';
import { LocaleSectionCard } from '../LocaleSectionCard';
import { PreferencesSectionCard } from '../PreferencesSectionCard';
import { ProfileSectionCard } from '../ProfileSectionCard';

export const ProfileTabPanel: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const preferencesBitfield = auth?.user.preferencesBitfield as number;

  return (
    <div className="flex flex-col gap-4">
      <ProfileSectionCard />

      {!auth?.user.isMuted ? <AvatarSection /> : null}

      <PreferencesSectionCard currentPreferencesBitfield={preferencesBitfield} />

      <LocaleSectionCard />
    </div>
  );
};
