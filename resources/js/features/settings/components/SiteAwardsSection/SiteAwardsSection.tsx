import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';

import { SectionStandardCard } from '../SectionStandardCard';

export const SiteAwardsSection: FC = () => {
  const { t } = useTranslation();

  return (
    <SectionStandardCard t_headingLabel={t('Site Awards')}>
      <div className="flex flex-col gap-4">
        <p>{t('You can manually reorder how badges appear on your user profile.')}</p>

        <BaseButton className="self-start" size="sm" asChild>
          <a href="/reorderSiteAwards.php">{t('Reorder Site Awards')}</a>
        </BaseButton>
      </div>
    </SectionStandardCard>
  );
};
