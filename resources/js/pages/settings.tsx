import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SettingsRoot } from '@/features/settings/components/+root';
import { requestedUsernameAtom, settingsTabAtom } from '@/features/settings/state/settings.atoms';

const Settings: AppPage = () => {
  const { initialTab, requestedUsername } =
    usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [settingsTabAtom, initialTab],
    [requestedUsernameAtom, requestedUsername ?? undefined],
    //
  ]);

  return (
    <>
      <SEO title={t('Settings')} description="Adjust your account's settings and preferences." />

      <AppLayout.Main>
        <SettingsRoot />
      </AppLayout.Main>
    </>
  );
};

Settings.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Settings;
