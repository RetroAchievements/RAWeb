import type { FC, ReactNode } from 'react';

import type { TranslatedString } from '@/types/i18next';

interface TooltipCreditsSectionProps {
  children: ReactNode;
  headingLabel: TranslatedString;
}

export const TooltipCreditsSection: FC<TooltipCreditsSectionProps> = ({
  children,
  headingLabel,
}) => {
  return (
    <div>
      <p className="font-bold">{headingLabel}</p>
      <div className="flex flex-col gap-1">{children}</div>
    </div>
  );
};
