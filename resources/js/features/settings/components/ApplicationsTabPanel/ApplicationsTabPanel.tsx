import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { KeysSectionCard } from '../KeysSectionCard';

export const ApplicationsTabPanel: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

  if (!can.manipulateApiKeys) {
    return <EmptyState>{t('Verify your email address to manage API keys.')}</EmptyState>;
  }

  return <KeysSectionCard />;
};
