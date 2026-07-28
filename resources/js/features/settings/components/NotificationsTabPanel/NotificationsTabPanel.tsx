import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { NotificationsSectionCard } from '../NotificationsSectionCard';

export const NotificationsTabPanel: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  return (
    <NotificationsSectionCard
      currentPreferencesBitfield={auth?.user.preferencesBitfield as number}
    />
  );
};
