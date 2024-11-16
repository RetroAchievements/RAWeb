/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as ToggleGroupPrimitive from '@radix-ui/react-toggle-group';
import { type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/utils/cn';

import { baseToggleVariants } from './BaseToggle';

const BaseToggleGroupContext = React.createContext<VariantProps<typeof baseToggleVariants>>({
  size: 'default',
  variant: 'default',
});

const BaseToggleGroup = React.forwardRef<
  React.ElementRef<typeof ToggleGroupPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof ToggleGroupPrimitive.Root> &
    VariantProps<typeof baseToggleVariants>
>(({ className, variant, size, children, ...props }, ref) => (
  <ToggleGroupPrimitive.Root
    ref={ref}
    className={cn('flex items-center justify-center gap-1', className)}
    {...props}
  >
    <BaseToggleGroupContext.Provider value={{ variant, size }}>
      {children}
    </BaseToggleGroupContext.Provider>
  </ToggleGroupPrimitive.Root>
));
BaseToggleGroup.displayName = ToggleGroupPrimitive.Root.displayName;

const BaseToggleGroupItem = React.forwardRef<
  React.ElementRef<typeof ToggleGroupPrimitive.Item>,
  React.ComponentPropsWithoutRef<typeof ToggleGroupPrimitive.Item> &
    VariantProps<typeof baseToggleVariants>
>(({ className, children, variant, size, ...props }, ref) => {
  const context = React.useContext(BaseToggleGroupContext);

  return (
    <ToggleGroupPrimitive.Item
      ref={ref}
      className={cn(
        baseToggleVariants({
          variant: context.variant || variant,
          size: context.size || size,
        }),
        className,
      )}
      {...props}
    >
      {children}
    </ToggleGroupPrimitive.Item>
  );
});
BaseToggleGroupItem.displayName = ToggleGroupPrimitive.Item.displayName;

export { BaseToggleGroup, BaseToggleGroupItem };
