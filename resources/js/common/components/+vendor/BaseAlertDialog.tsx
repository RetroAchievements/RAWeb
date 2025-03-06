/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as AlertDialogPrimitive from '@radix-ui/react-alert-dialog';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from './BaseButton';

const BaseAlertDialog = AlertDialogPrimitive.Root;
const BaseAlertDialogTrigger = AlertDialogPrimitive.Trigger;
const BaseAlertDialogPortal = AlertDialogPrimitive.Portal;

const BaseAlertDialogOverlay = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Overlay>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Overlay>
>(({ className, ...props }, ref) => (
  <AlertDialogPrimitive.Overlay
    className={cn(
      'fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
      className,
    )}
    {...props}
    ref={ref}
  />
));
BaseAlertDialogOverlay.displayName = AlertDialogPrimitive.Overlay.displayName;

const BaseAlertDialogContent = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Content> & {
    shouldBlurBackdrop?: boolean;
  }
>(({ className, shouldBlurBackdrop = false, ...props }, ref) => (
  <BaseAlertDialogPortal>
    <BaseAlertDialogOverlay className={cn(shouldBlurBackdrop ? 'backdrop-blur' : '')} />

    <AlertDialogPrimitive.Content
      ref={ref}
      className={cn(
        'fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] bg-embed',
        'gap-4 border border-neutral-600 p-6 shadow-lg duration-200 data-[state=open]:animate-in light:border-neutral-200',
        'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
        'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2',
        'data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2',
        'data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg',
        className,
      )}
      {...props}
    />
  </BaseAlertDialogPortal>
));
BaseAlertDialogContent.displayName = AlertDialogPrimitive.Content.displayName;

const BaseAlertDialogHeader = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('flex flex-col space-y-2 text-center sm:text-left', className)} {...props} />
);
BaseAlertDialogHeader.displayName = 'BaseAlertDialogHeader';

const BaseAlertDialogFooter = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div
    className={cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2', className)}
    {...props}
  />
);
BaseAlertDialogFooter.displayName = 'BaseAlertDialogFooter';

const BaseAlertDialogTitle = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Title>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Title>
>(({ className, ...props }, ref) => (
  <AlertDialogPrimitive.Title
    ref={ref}
    className={cn('text-lg font-semibold', className)}
    {...props}
  />
));
BaseAlertDialogTitle.displayName = AlertDialogPrimitive.Title.displayName;

const BaseAlertDialogDescription = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Description>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Description>
>(({ className, ...props }, ref) => (
  <AlertDialogPrimitive.Description
    ref={ref}
    className={cn('text-muted-foreground text-sm', className)}
    {...props}
  />
));
BaseAlertDialogDescription.displayName = AlertDialogPrimitive.Description.displayName;

const BaseAlertDialogAction = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Action>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Action>
>(({ className, ...props }, ref) => (
  <AlertDialogPrimitive.Action
    ref={ref}
    className={cn(baseButtonVariants(), className)}
    {...props}
  />
));
BaseAlertDialogAction.displayName = AlertDialogPrimitive.Action.displayName;

const BaseAlertDialogCancel = React.forwardRef<
  React.ElementRef<typeof AlertDialogPrimitive.Cancel>,
  React.ComponentPropsWithoutRef<typeof AlertDialogPrimitive.Cancel>
>(({ className, ...props }, ref) => (
  <AlertDialogPrimitive.Cancel
    ref={ref}
    className={cn(
      baseButtonVariants({ variant: 'link', className: 'text-link hover:text-link-hover' }),
      'mt-2 sm:mt-0',
      className,
    )}
    {...props}
  />
));
BaseAlertDialogCancel.displayName = AlertDialogPrimitive.Cancel.displayName;

export {
  BaseAlertDialog,
  BaseAlertDialogAction,
  BaseAlertDialogCancel,
  BaseAlertDialogContent,
  BaseAlertDialogDescription,
  BaseAlertDialogFooter,
  BaseAlertDialogHeader,
  BaseAlertDialogOverlay,
  BaseAlertDialogPortal,
  BaseAlertDialogTitle,
  BaseAlertDialogTrigger,
};
