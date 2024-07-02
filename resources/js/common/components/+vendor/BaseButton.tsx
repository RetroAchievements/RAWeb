import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { type ButtonHTMLAttributes, forwardRef } from 'react';

import { cn } from '@/utils/cn';

const baseButtonVariants = cva(
  [
    'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium light:ring-offset-white',
    'focus-visible:outline-none focus-visible:ring-2 light:focus-visible:ring-neutral-950 focus-visible:ring-offset-2',
    'disabled:pointer-events-none disabled:opacity-50 ring-offset-neutral-950 focus-visible:ring-neutral-300',
  ],
  {
    variants: {
      variant: {
        default:
          'light:bg-neutral-900 light:text-neutral-50 light:hover:bg-neutral-900/90 bg-neutral-50 text-neutral-900 hover:bg-neutral-50/90',
        destructive:
          'light:bg-red-500 light:text-neutral-50 light:hover:bg-red-500/90 bg-red-900 text-neutral-50 hover:bg-red-900/90',
        outline:
          'border light:border-neutral-200 light:bg-white light:hover:bg-neutral-100 light:hover:text-neutral-900 border-neutral-800 bg-neutral-950 hover:bg-neutral-800 hover:text-neutral-50',
        secondary:
          'light:bg-neutral-100 light:text-neutral-900 light:hover:bg-neutral-100/80 bg-neutral-800 text-neutral-50 hover:bg-neutral-800/80',
        ghost:
          'light:hover:bg-neutral-100 light:hover:text-neutral-900 hover:bg-neutral-800 hover:text-neutral-50',
        link: 'light:text-neutral-900 underline-offset-4 hover:underline text-neutral-50',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-9 rounded-md px-3',
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
    return (
      <Comp className={cn(baseButtonVariants({ variant, size, className }))} ref={ref} {...props} />
    );
  },
);
BaseButton.displayName = 'BaseButton';

export { BaseButton, baseButtonVariants };
