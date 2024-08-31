import { type FC } from 'react';

import { usePageProps } from '../../hooks/usePageProps';
import { SectionStandardCard } from '../SectionStandardCard';
import { ManageConnectApiKey } from './ManageConnectApiKey';
import { ManageWebApiKey } from './ManageWebApiKey';

export const KeysSectionCard: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  if (!can.manipulateApiKeys) {
    return null;
  }

  return (
    <SectionStandardCard headingLabel="Keys">
      <div className="flex flex-col gap-8">
        <ManageWebApiKey />
        <ManageConnectApiKey />
      </div>
    </SectionStandardCard>
  );
};
