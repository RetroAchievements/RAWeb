import type { FC, ReactNode } from 'react';

import {
  BaseCard,
  BaseCardContent,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';

interface SectionStandardCardProps {
  t_headingLabel: string;
  children: ReactNode;
}

export const SectionStandardCard: FC<SectionStandardCardProps> = ({ t_headingLabel, children }) => {
  return (
    <BaseCard className="w-full">
      <BaseCardHeader className="pb-4">
        <BaseCardTitle>{t_headingLabel}</BaseCardTitle>
      </BaseCardHeader>

      <BaseCardContent>{children}</BaseCardContent>
    </BaseCard>
  );
};
