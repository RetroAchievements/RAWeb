/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const BaseTooltipProvider = TooltipPrimitive.Provider;

const BaseTooltip = TooltipPrimitive.Root;

const isInteractiveElement = (children: React.ReactNode): boolean => {
  if (!React.isValidElement(children)) return false;

  // Check if the element type matches interactive elements.
  const isInteractiveType = (type: string): boolean =>
    ['button', 'a', 'input', 'select', 'textarea'].includes(type.toLowerCase());

  // For native elements, just check the HTML tag.
  if (typeof children.type === 'string') {
    return isInteractiveType(children.type);
  }

  // For custom components, check if they render to an interactive element.
  // Look for common props that indicate interactive elements.
  const props = children.props as {
    role?: string;
    onClick?: unknown;
    href?: string;
    as?: string | React.ComponentType;
  };

  return !!(
    props.onClick ||
    props.href ||
    props.role === 'button' ||
    (props.as && typeof props.as === 'string' && isInteractiveType(props.as))
  );
};

const BaseTooltipTrigger = React.forwardRef<
  React.ElementRef<typeof TooltipPrimitive.Trigger>,
  React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Trigger> & {
    /** Explicit override. When undefined, it'll be auto-detected. */
    hasHelpCursor?: boolean;
  }
>(({ className, hasHelpCursor, children, ...props }, ref) => {
  const shouldShowHelpCursor = hasHelpCursor ?? !isInteractiveElement(children);

  return (
    <TooltipPrimitive.Trigger
      ref={ref}
      className={cn(shouldShowHelpCursor ? 'cursor-help' : null, className)}
      {...props}
    >
      {children}
    </TooltipPrimitive.Trigger>
  );
});
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
