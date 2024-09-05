import type { FC } from 'react';

import { usePageProps } from '../../hooks/usePageProps';
import { AvatarSection } from '../AvatarSection';
import { SiteAwardsSection } from '../SiteAwardsSection';

export const SettingsSidebar: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  // Just to improve type safety.
  if (!auth?.user) {
    return null;
  }

  return (
    <div className="flex flex-col gap-8">
      <SiteAwardsSection />

      {auth.user.isMuted ? null : (
        <>
          <hr className="border-neutral-700 light:border-neutral-300" />

          <AvatarSection />
        </>
      )}
    </div>
  );
};
