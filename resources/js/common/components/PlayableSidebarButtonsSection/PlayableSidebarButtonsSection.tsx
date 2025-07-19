import type { FC, ReactNode } from 'react';

import type { TranslatedString } from '@/types/i18next';

interface PlayableSidebarButtonsSectionProps {
  children: ReactNode;
  headingLabel: TranslatedString;
}

export const PlayableSidebarButtonsSection: FC<PlayableSidebarButtonsSectionProps> = ({
  children,
  headingLabel,
}) => {
  return (
    <div className="flex flex-col gap-1">
      <p className="text-xs text-neutral-300 light:text-neutral-800">{headingLabel}</p>

      {children}
    </div>
  );
};
