import { usePage } from '@inertiajs/react';
import { type FC } from 'react';

import type { SettingsPageProps } from '../../models';
import { SectionStandardCard } from '../SectionStandardCard';
import { ManageConnectApiKey } from './ManageConnectApiKey';
import { ManageWebApiKey } from './ManageWebApiKey';

export const KeysSectionCard: FC = () => {
  const {
    props: { can },
  } = usePage<SettingsPageProps>();

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
