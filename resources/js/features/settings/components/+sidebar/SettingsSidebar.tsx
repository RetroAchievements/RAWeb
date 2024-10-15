import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { AuthenticatedUser } from '@/common/models';

import { AvatarSection } from '../AvatarSection';
import { SiteAwardsSection } from '../SiteAwardsSection';

export const SettingsSidebar: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  return (
    <div className="flex flex-col gap-8">
      <SiteAwardsSection />

      {(auth as { user: AuthenticatedUser }).user.isMuted ? null : (
        <>
          <hr className="border-neutral-700 light:border-neutral-300" />

          <AvatarSection />
        </>
      )}
    </div>
  );
};
