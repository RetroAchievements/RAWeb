import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { ConnectedApplicationsSection } from '../ConnectedApplicationsSection';
import { KeysSectionCard } from '../KeysSectionCard';
import { YourApplicationsSection } from '../YourApplicationsSection';

export const ApplicationsTabPanel: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-6">
      <ConnectedApplicationsSection />

      {can.manipulateApiKeys ? (
        <KeysSectionCard />
      ) : (
        <p>{t('Verify your email address to manage API keys.')}</p>
      )}

      {can.viewAnyOAuthClients ? <YourApplicationsSection /> : null}
    </div>
  );
};
