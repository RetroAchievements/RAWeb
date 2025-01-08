import type { FC, ReactNode } from 'react';

import { BaseCard, BaseCardContent, BaseCardHeader } from '@/common/components/+vendor/BaseCard';
import type { TranslatedString } from '@/types/i18next';

interface FeedStatCardProps {
  children: ReactNode;
  t_label: TranslatedString;
}

export const FeedStatCard: FC<FeedStatCardProps> = ({ children, t_label }) => {
  return (
    <BaseCard>
      <BaseCardHeader className="pb-1">{t_label}</BaseCardHeader>
      <BaseCardContent>
        <p className="text-xl">{children}</p>
      </BaseCardContent>
    </BaseCard>
  );
};
