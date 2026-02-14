/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { cva, type VariantProps } from 'class-variance-authority';
import { Tabs as TabsPrimitive } from 'radix-ui';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const BaseTabs = TabsPrimitive.Root;

const BaseTabsList = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.List>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.List>
>(({ className, ...props }, ref) => (
  <TabsPrimitive.List
    ref={ref}
    className={cn(
      'inline-flex h-10 items-center justify-center rounded-md bg-neutral-800 p-1 text-neutral-300',
      className,
    )}
    {...props}
  />
));
BaseTabsList.displayName = TabsPrimitive.List.displayName;

const baseTabsTriggerVariants = cva([], {
  variants: {
    variant: {
      default: cn(
        'ring-offset-background focus-visible:ring-ring data-[state=active]:text-foreground',
        'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5',
        'text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        'disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-neutral-950 data-[state=active]:shadow-sm',
      ),
      underlined: cn(
        'text-center h-full px-3 font-medium text-xs text-neutral-500 light:text-neutral-700 border-b-2 light:border-b-4 border-transparent flex-1 md:flex-initial whitespace-nowrap',
        'data-[state=active]:text-link data-[state=active]:border-link data-[state=active]:light:border-neutral-900',
        'transition-all duration-200',
      ),
    },
  },
  defaultVariants: {
    variant: 'default',
  },
});

type BaseTabsTriggerProps = React.ComponentPropsWithoutRef<typeof TabsPrimitive.Trigger> &
  VariantProps<typeof baseTabsTriggerVariants>;

const BaseTabsTrigger = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.Trigger>,
  BaseTabsTriggerProps
>(({ className, variant, ...props }, ref) => (
  <TabsPrimitive.Trigger
    ref={ref}
    className={baseTabsTriggerVariants({ variant, className })}
    {...props}
  />
));
BaseTabsTrigger.displayName = TabsPrimitive.Trigger.displayName;

const BaseTabsContent = React.forwardRef<
  React.ElementRef<typeof TabsPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.Content>
>(({ className, ...props }, ref) => (
  <TabsPrimitive.Content
    ref={ref}
    className={cn(
      'ring-offset-background focus-visible:ring-ring mt-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
      className,
    )}
    {...props}
  />
));
BaseTabsContent.displayName = TabsPrimitive.Content.displayName;

export { BaseTabs, BaseTabsContent, BaseTabsList, BaseTabsTrigger };
