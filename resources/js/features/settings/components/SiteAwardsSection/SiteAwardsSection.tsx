import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { baseCardTitleClassNames } from '@/common/components/+vendor/BaseCard';
import { cn } from '@/common/utils/cn';

export const SiteAwardsSection: FC = () => {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-4">
      <h3 className={cn(baseCardTitleClassNames, 'text-2xl')}>{t('Site Awards')}</h3>
      <p>{t('You can manually reorder how badges appear on your user profile.')}</p>

      <BaseButton size="sm" asChild>
        <a href="/reorderSiteAwards.php">{t('Reorder Site Awards')}</a>
      </BaseButton>
    </div>
  );
};
