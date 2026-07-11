import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { SectionStandardCard } from '../SectionStandardCard';
import { ManageConnectApiKey } from './ManageConnectApiKey';
import { ManageWebApiKey } from './ManageWebApiKey';

export const KeysSectionCard: FC = () => {
  const { t } = useTranslation();

  return (
    <SectionStandardCard t_headingLabel={t('API Access')}>
      <div className="flex flex-col gap-8">
        <ManageWebApiKey />
        <ManageConnectApiKey />
      </div>
    </SectionStandardCard>
  );
};
