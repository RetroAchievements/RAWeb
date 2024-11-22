/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as TogglePrimitive from '@radix-ui/react-toggle';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/utils/cn';

const baseToggleVariants = cva(
  cn(
    'inline-flex items-center justify-center rounded-md text-sm text-neutral-500 font-medium transition-colors',
    'hover:bg-neutral-900 light:hover:bg-neutral-500 hover:text-neutral-50',
    'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    'disabled:pointer-events-none disabled:opacity-50',
    'data-[state=on]:bg-embed-highlight data-[state=on]:text-neutral-50',
    'data-[state=on]:light:bg-neutral-600',
  ),
  {
    variants: {
      variant: {
        default: 'bg-transparent',
        outline: 'border border-embed-highlight bg-transparent shadow-sm',
      },
      size: {
        default: 'h-9 px-3',
        sm: 'h-8 px-2',
        lg: 'h-10 px-3',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

const BaseToggle = React.forwardRef<
  React.ElementRef<typeof TogglePrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof TogglePrimitive.Root> &
    VariantProps<typeof baseToggleVariants>
>(({ className, variant, size, ...props }, ref) => (
  <TogglePrimitive.Root
    ref={ref}
    className={cn(baseToggleVariants({ variant, size, className }))}
    {...props}
  />
));
BaseToggle.displayName = TogglePrimitive.Root.displayName;

export { BaseToggle, baseToggleVariants };
