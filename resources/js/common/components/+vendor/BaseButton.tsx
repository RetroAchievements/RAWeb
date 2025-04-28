/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { type ButtonHTMLAttributes, forwardRef, useMemo } from 'react';

import { cn } from '@/common/utils/cn';

const baseButtonVariants = cva(
  [
    'inline-flex items-center justify-center whitespace-nowrap rounded text-sm font-medium light:ring-offset-white',
    'focus-visible:outline-none focus-visible:ring-1 light:focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
    'disabled:pointer-events-none disabled:opacity-50',
    'ring-offset-neutral-950 focus-visible:ring-neutral-300 select-none',
    'lg:active:translate-y-[1px] lg:active:scale-[0.98] lg:disabled:active:scale-100 lg:disabled:active:translate-y-0 lg:transition-transform lg:duration-100',
  ],
  {
    variants: {
      variant: {
        default: cn(
          'bg-embed text-link border border-neutral-700 hover:bg-embed-highlight hover:text-link-hover hover:border-menu-link',
          'light:bg-white light:border-link light:text-link light:hover:bg-neutral-100',
          'disabled:hover:bg-embed disabled:hover:text-link disabled:hover:border-neutral-700',
          'disabled:light:hover:bg-white disabled:light:hover:text-link disabled:light:hover:border-link',
        ),
        destructive: 'bg-embed border btn-danger hover:text-link-hover hover:border-menu-link',
        outline: cn(
          'border light:border-neutral-200 light:bg-white light:hover:bg-neutral-100 light:hover:text-neutral-900',
          'border-neutral-800 bg-neutral-950 hover:bg-neutral-800 hover:text-neutral-50',
        ),
        secondary:
          'light:bg-neutral-200 light:text-neutral-900 bg-neutral-800 text-neutral-50 hover:bg-neutral-800/80',
        ghost:
          'light:hover:bg-neutral-100 light:hover:text-neutral-900 hover:bg-neutral-800 hover:text-neutral-50',
        link: 'light:text-neutral-900 underline-offset-4 hover:underline text-neutral-50',
      },
      size: {
        default: 'h-9 px-4 py-2',
        sm: 'h-[30px] rounded-md px-3 !text-[13px]',
        lg: 'h-11 rounded-md px-8',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

export interface BaseButtonProps
  extends ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof baseButtonVariants> {
  asChild?: boolean;
}

const BaseButton = forwardRef<HTMLButtonElement, BaseButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';

    const computedClassName = useMemo(
      () => cn(baseButtonVariants({ variant, size, className })),
      [variant, size, className],
    );

    return <Comp className={computedClassName} ref={ref} {...props} />;
  },
);
BaseButton.displayName = 'BaseButton';

export { BaseButton, baseButtonVariants };
