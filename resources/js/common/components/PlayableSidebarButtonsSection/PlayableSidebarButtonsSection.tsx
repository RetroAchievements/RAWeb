import type { FC, ReactNode } from 'react';
import { Children } from 'react';

import type { TranslatedString } from '@/types/i18next';

interface PlayableSidebarButtonsSectionProps {
  children: ReactNode;
  headingLabel: TranslatedString;
}

export const PlayableSidebarButtonsSection: FC<PlayableSidebarButtonsSectionProps> = ({
  children,
  headingLabel,
}) => {
  // If there aren't any buttons to render (due to children returning null),
  // then don't render headingLabel either.
  const hasVisibleChildren = Children.toArray(children).some((child) => child);
  if (!hasVisibleChildren) {
    return null;
  }

  return (
    <div className="flex flex-col gap-1">
      <p className="text-xs text-neutral-300 light:text-neutral-800">{headingLabel}</p>

      {children}
    </div>
  );
};
