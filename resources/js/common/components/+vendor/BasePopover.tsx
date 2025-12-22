/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { Popover as PopoverPrimitive } from 'radix-ui';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const BasePopover = PopoverPrimitive.Root;

const BasePopoverTrigger = PopoverPrimitive.Trigger;

const BasePopoverAnchor = PopoverPrimitive.Anchor;

const BasePopoverContent = React.forwardRef<
  React.ElementRef<typeof PopoverPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof PopoverPrimitive.Content>
>(({ className, align = 'center', sideOffset = 4, ...props }, ref) => (
  <PopoverPrimitive.Portal>
    <PopoverPrimitive.Content
      ref={ref}
      align={align}
      sideOffset={sideOffset}
      className={cn(
        'z-50 w-72 rounded-md border border-neutral-700 bg-neutral-950 p-4 shadow-md outline-none light:bg-white',
        'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0',
        'data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
        'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
        'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        'text-menu-link',
        className,
      )}
      {...props}
    />
  </PopoverPrimitive.Portal>
));
BasePopoverContent.displayName = PopoverPrimitive.Content.displayName;

export { BasePopover, BasePopoverAnchor, BasePopoverContent, BasePopoverTrigger };
