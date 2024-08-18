import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import type { SettingsPageProps } from '../../models';
import { AvatarSection } from '../AvatarSection';
import { SiteAwardsSection } from '../SiteAwardsSection';

export const SettingsSidebar: FC = () => {
  const {
    props: { auth },
  } = usePage<SettingsPageProps>();

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
