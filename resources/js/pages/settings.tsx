import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SettingsRoot } from '@/features/settings/components/+root';
import { SettingsSidebar } from '@/features/settings/components/+sidebar';
import { requestedUsernameAtom } from '@/features/settings/state/settings.atoms';

const Settings: AppPage = () => {
  const { requestedUsername } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [requestedUsernameAtom, requestedUsername ?? undefined],
    //
  ]);

  return (
    <>
      <SEO title={t('Settings')} description="Adjust your account's settings and preferences." />

      <div className="container">
        <AppLayout.Main>
          <SettingsRoot />
        </AppLayout.Main>
      </div>

      <AppLayout.Sidebar>
        <SettingsSidebar />
      </AppLayout.Sidebar>
    </>
  );
};

Settings.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default Settings;
