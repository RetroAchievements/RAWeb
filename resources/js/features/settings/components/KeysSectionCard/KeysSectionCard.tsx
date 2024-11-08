import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionStandardCard } from '../SectionStandardCard';
import { ManageConnectApiKey } from './ManageConnectApiKey';
import { ManageWebApiKey } from './ManageWebApiKey';

export const KeysSectionCard: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useLaravelReactI18n();

  if (!can.manipulateApiKeys) {
    return null;
  }

  return (
    <SectionStandardCard t_headingLabel={t('Keys')}>
      <div className="flex flex-col gap-8">
        <ManageWebApiKey />
        <ManageConnectApiKey />
      </div>
    </SectionStandardCard>
  );
};
