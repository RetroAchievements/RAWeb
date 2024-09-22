import type { FC, ReactNode } from 'react';

import {
  BaseCard,
  BaseCardContent,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';

interface SectionStandardCardProps {
  headingLabel: string;
  children: ReactNode;
}

export const SectionStandardCard: FC<SectionStandardCardProps> = ({ headingLabel, children }) => {
  return (
    <BaseCard className="w-full">
      <BaseCardHeader className="pb-4">
        <BaseCardTitle>{headingLabel}</BaseCardTitle>
      </BaseCardHeader>

      <BaseCardContent>{children}</BaseCardContent>
    </BaseCard>
  );
};
