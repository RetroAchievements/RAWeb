import type { FC, ReactNode } from 'react';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

/**
 * 👉 If you find yourself reaching to add more props, use BaseTooltip instead.
 */

interface SimpleTooltipProps {
  children: ReactNode;
  tooltipContent: string;

  /** Use this to conditionally control whether the tooltip is visible. */
  isOpen?: boolean;

  /** If the tooltip is being added to something like a button, this needs to be truthy. */
  isWrappingTapTarget?: boolean;
}

export const SimpleTooltip: FC<SimpleTooltipProps> = ({
  children,
  isWrappingTapTarget,
  tooltipContent,
  isOpen,
}) => {
  return (
    <BaseTooltip open={isOpen}>
      <BaseTooltipTrigger asChild={isWrappingTapTarget}>{children}</BaseTooltipTrigger>

      <BaseTooltipContent>{tooltipContent}</BaseTooltipContent>
    </BaseTooltip>
  );
};
