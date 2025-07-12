import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionStandardCard } from '../SectionStandardCard';
import { ManageConnectApiKey } from './ManageConnectApiKey';
import { ManageWebApiKey } from './ManageWebApiKey';

export const KeysSectionCard: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  if (!can.manipulateApiKeys) {
    return null;
  }

  return (
    <SectionStandardCard t_headingLabel={t('Authentication')}>
      <div className="flex flex-col gap-8">
        <ManageWebApiKey />
        <ManageConnectApiKey />
      </div>
    </SectionStandardCard>
  );
};
