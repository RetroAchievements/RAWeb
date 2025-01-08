/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const BaseTooltipProvider = TooltipPrimitive.Provider;

const BaseTooltip = TooltipPrimitive.Root;

const BaseTooltipTrigger = React.forwardRef<
  React.ElementRef<typeof TooltipPrimitive.Trigger>,
  React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Trigger>
>(({ className, ...props }, ref) => (
  <TooltipPrimitive.Trigger ref={ref} className={cn('cursor-help', className)} {...props} />
));
BaseTooltipTrigger.displayName = 'BaseTooltipTrigger';

const BaseTooltipPortal = TooltipPrimitive.Portal;

const BaseTooltipContent = React.forwardRef<
  React.ElementRef<typeof TooltipPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Content>
>(({ className, sideOffset = 4, ...props }, ref) => (
  <TooltipPrimitive.Content
    ref={ref}
    sideOffset={sideOffset}
    className={cn(
      'z-50 overflow-hidden rounded-md border px-3 py-1.5 text-sm shadow-md light:border-neutral-200 light:bg-white',
      'animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
      'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2',
      'border-neutral-800 bg-neutral-950 text-menu-link data-[side=top]:slide-in-from-bottom-2',
      className,
    )}
    {...props}
  />
));
BaseTooltipContent.displayName = 'BaseTooltipContent';

export {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipProvider,
  BaseTooltipTrigger,
};
