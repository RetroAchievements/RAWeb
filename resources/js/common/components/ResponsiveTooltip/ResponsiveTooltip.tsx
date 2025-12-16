import type { ComponentPropsWithoutRef, FC, ReactNode } from 'react';
import { createContext, useContext, useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BasePopover, BasePopoverContent, BasePopoverTrigger } from '../+vendor/BasePopover';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

/**
 * A responsive tooltip that shows as a hover tooltip on desktop
 * and as a tap-to-toggle popover on mobile.
 *
 * Use this instead of BaseTooltip when the tooltip content needs to be
 * accessible on mobile devices.
 */

interface ResponsiveTooltipContextValue {
  isMobile: boolean;
  isOpen: boolean;
  setIsOpen: (open: boolean) => void;
}

const ResponsiveTooltipContext = createContext<ResponsiveTooltipContextValue | null>(null);

interface ResponsiveTooltipProps {
  children: ReactNode;
}

export const ResponsiveTooltip: FC<ResponsiveTooltipProps> = ({ children }) => {
  const { ziggy } = usePageProps();

  const [isOpen, setIsOpen] = useState(false);

  const isMobile = ziggy?.device === 'mobile';

  // Desktop: use a tooltip (hover only).
  if (!isMobile) {
    return <BaseTooltip>{children}</BaseTooltip>;
  }

  // Mobile: use a popover (tap to toggle).
  return (
    <ResponsiveTooltipContext.Provider value={{ isMobile, isOpen, setIsOpen }}>
      <BasePopover open={isOpen} onOpenChange={setIsOpen}>
        {children}
      </BasePopover>
    </ResponsiveTooltipContext.Provider>
  );
};

type ResponsiveTooltipTriggerProps = ComponentPropsWithoutRef<typeof BaseTooltipTrigger>;

export const ResponsiveTooltipTrigger: FC<ResponsiveTooltipTriggerProps> = (props) => {
  const context = useContext(ResponsiveTooltipContext);

  if (!context) {
    return <BaseTooltipTrigger {...props} />;
  }

  return <BasePopoverTrigger {...props} />;
};

type ResponsiveTooltipContentProps = ComponentPropsWithoutRef<typeof BaseTooltipContent>;

export const ResponsiveTooltipContent: FC<ResponsiveTooltipContentProps> = (props) => {
  const context = useContext(ResponsiveTooltipContext);

  if (!context) {
    return <BaseTooltipContent {...props} />;
  }

  return <BasePopoverContent {...props} />;
};
