import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SettingsRoot } from '@/features/settings/components/+root';
import { SettingsSidebar } from '@/features/settings/components/+sidebar';

const Settings: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Settings')}>
        <meta name="description" content="Adjust your account's settings and preferences." />
      </Head>

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
