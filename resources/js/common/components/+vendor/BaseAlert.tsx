import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/utils/cn';

const baseAlertVariants = cva(
  'relative w-full rounded border light:border-neutral-200 p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-3.5 [&>svg]:top-4 light:[&>svg]:text-neutral-950 border-neutral-800 [&>svg]:text-neutral-50',
  {
    variants: {
      variant: {
        default: 'light:bg-white light:text-neutral-950 bg-neutral-950 text-neutral-50',
        destructive:
          'light:bg-red-50 bg-red-900/10 light:border-red-500/50 text-red-500 border-red-500 light:[&>svg]:text-red-500 text-red-500 [&>svg]:text-red-500',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

const BaseAlert = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement> & VariantProps<typeof baseAlertVariants>
>(({ className, variant, ...props }, ref) => (
  <div
    ref={ref}
    role="alert"
    className={cn(baseAlertVariants({ variant }), className)}
    {...props}
  />
));
BaseAlert.displayName = 'BaseAlert';

const BaseAlertTitle = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLHeadingElement>
>(({ className, ...props }, ref) => (
  <h5
    ref={ref}
    className={cn('mb-1 text-sm font-medium leading-none tracking-tight', className)}
    {...props}
  />
));
BaseAlertTitle.displayName = 'BaseAlertTitle';

const BaseAlertDescription = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn('[&_p]:leading-relaxed', className)} {...props} />
));
BaseAlertDescription.displayName = 'BaseAlertDescription';

export { BaseAlert, BaseAlertDescription, BaseAlertTitle };
