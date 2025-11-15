/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { HoverCard as HoverCardPrimitive } from 'radix-ui';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const BaseHoverCard = HoverCardPrimitive.Root;

const BaseHoverCardTrigger = HoverCardPrimitive.Trigger;

const BaseHoverCardContent = React.forwardRef<
  React.ElementRef<typeof HoverCardPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof HoverCardPrimitive.Content>
>(({ className, align = 'center', sideOffset = 4, ...props }, ref) => (
  <HoverCardPrimitive.Content
    ref={ref}
    align={align}
    sideOffset={sideOffset}
    className={cn(
      'bg-popover z-50 w-64 border text-xs light:border-neutral-200 light:bg-white',
      'border-neutral-800 bg-neutral-950 text-menu-link',
      'origin-[--radix-hover-card-content-transform-origin] rounded-md border p-4 shadow-md',
      'outline-none data-[state=open]:animate-in data-[state=closed]:animate-out',
      'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95',
      'data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
      'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
      className,
    )}
    {...props}
  />
));
BaseHoverCardContent.displayName = HoverCardPrimitive.Content.displayName;

export { BaseHoverCard, BaseHoverCardContent, BaseHoverCardTrigger };
