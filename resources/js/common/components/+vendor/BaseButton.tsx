/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { cva, type VariantProps } from 'class-variance-authority';
import { Slot } from 'radix-ui';
import { type ButtonHTMLAttributes, forwardRef, useMemo } from 'react';

import { cn } from '@/common/utils/cn';

const baseButtonVariants = cva(['btn-base'], {
  variants: {
    variant: {
      default: 'btn-base--default',
      defaultDisabled: 'btn-base--default-disabled',
      destructive: 'btn-base--destructive',
      outline: 'btn-base--outline',
      secondary: 'btn-base--secondary',
      ghost: 'btn-base--ghost',
      link: 'btn-base--link',
    },
    size: {
      default: 'btn-base--size-default',
      sm: 'btn-base--size-sm',
      lg: 'btn-base--size-lg',
      icon: 'btn-base--size-icon',
    },
  },
  defaultVariants: {
    variant: 'default',
    size: 'default',
  },
});

export interface BaseButtonProps
  extends ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof baseButtonVariants> {
  asChild?: boolean;
}

const BaseButton = forwardRef<HTMLButtonElement, BaseButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot.Root : 'button';

    const computedClassName = useMemo(
      () => cn(baseButtonVariants({ variant, size, className })),
      [variant, size, className],
    );

    return <Comp className={computedClassName} ref={ref} {...props} />;
  },
);
BaseButton.displayName = 'BaseButton';

export { BaseButton, baseButtonVariants };
