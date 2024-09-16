import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SettingsRoot } from '@/features/settings/components/+root';
import { SettingsSidebar } from '@/features/settings/components/+sidebar';

const Settings: AppPage = () => {
  return (
    <>
      <Head title="Settings">
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
