import type { FC } from 'react';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { baseCardTitleClassNames } from '@/common/components/+vendor/BaseCard';
import { cn } from '@/utils/cn';

export const SiteAwardsSection: FC = () => {
  return (
    <div className="flex flex-col gap-4">
      <h3 className={cn(baseCardTitleClassNames, 'text-2xl')}>Site Awards</h3>
      <p>You can manually reorder how badges appear on your user profile.</p>

      <BaseButton size="sm" asChild>
        <a href="/reorderSiteAwards.php">Reorder Site Awards</a>
      </BaseButton>
    </div>
  );
};
