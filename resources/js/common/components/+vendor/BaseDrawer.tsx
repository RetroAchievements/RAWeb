/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as React from 'react';
import { RxCross2 } from 'react-icons/rx';
import { Drawer as DrawerPrimitive } from 'vaul';

import { cn } from '@/utils/cn';

const BaseDrawer = ({
  shouldScaleBackground = true,
  ...props
}: React.ComponentProps<typeof DrawerPrimitive.Root>) => (
  <DrawerPrimitive.Root
    shouldScaleBackground={shouldScaleBackground}
    noBodyStyles={true}
    {...props}
  />
);
BaseDrawer.displayName = 'BaseDrawer';

const BaseDrawerTrigger = DrawerPrimitive.Trigger;

const BaseDrawerPortal = DrawerPrimitive.Portal;

const BaseDrawerClose = DrawerPrimitive.Close;

const BaseDrawerOverlay = React.forwardRef<
  React.ElementRef<typeof DrawerPrimitive.Overlay>,
  React.ComponentPropsWithoutRef<typeof DrawerPrimitive.Overlay>
>(({ className, ...props }, ref) => (
  <DrawerPrimitive.Overlay
    ref={ref}
    className={cn('fixed inset-0 z-50 bg-black/80', className)}
    {...props}
  />
));
BaseDrawerOverlay.displayName = DrawerPrimitive.Overlay.displayName;

const BaseDrawerContent = React.forwardRef<
  React.ElementRef<typeof DrawerPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof DrawerPrimitive.Content>
>(({ className, children, ...props }, ref) => (
  <BaseDrawerPortal>
    <BaseDrawerOverlay />
    <DrawerPrimitive.Content
      ref={ref}
      className={cn(
        'fixed inset-x-0 bottom-0 z-50 mt-24 flex h-auto flex-col rounded-t-3xl',
        'border border-neutral-800 bg-neutral-950 light:border-neutral-200 light:bg-neutral-50',
        className,
      )}
      {...props}
    >
      <BaseDrawerClose className="absolute right-3 top-3" asChild>
        <button
          className={cn(
            'flex h-8 w-8 items-center justify-center rounded-full',
            'bg-neutral-700 text-neutral-100 light:bg-neutral-300/70 light:text-neutral-950',
          )}
        >
          <RxCross2 className="h-4 w-4" />
        </button>
      </BaseDrawerClose>

      {children}
    </DrawerPrimitive.Content>
  </BaseDrawerPortal>
));
BaseDrawerContent.displayName = 'BaseDrawerContent';

const BaseDrawerHeader = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('grid gap-1.5 p-4 text-center sm:text-left', className)} {...props} />
);
BaseDrawerHeader.displayName = 'BaseDrawerHeader';

const BaseDrawerFooter = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('mt-auto flex flex-col gap-2 px-4 pb-6 pt-4', className)} {...props} />
);
BaseDrawerFooter.displayName = 'BaseDrawerFooter';

const BaseDrawerTitle = React.forwardRef<
  React.ElementRef<typeof DrawerPrimitive.Title>,
  React.ComponentPropsWithoutRef<typeof DrawerPrimitive.Title>
>(({ className, ...props }, ref) => (
  <DrawerPrimitive.Title
    ref={ref}
    className={cn(
      'text-center text-sm leading-none tracking-tight text-neutral-200 light:text-neutral-900',
      className,
    )}
    {...props}
  />
));
BaseDrawerTitle.displayName = DrawerPrimitive.Title.displayName;

const BaseDrawerDescription = React.forwardRef<
  React.ElementRef<typeof DrawerPrimitive.Description>,
  React.ComponentPropsWithoutRef<typeof DrawerPrimitive.Description>
>(({ className, ...props }, ref) => (
  <DrawerPrimitive.Description
    ref={ref}
    className={cn('text-sm text-neutral-300 light:text-neutral-600', className)}
    {...props}
  />
));
BaseDrawerDescription.displayName = DrawerPrimitive.Description.displayName;

export {
  BaseDrawer,
  BaseDrawerClose,
  BaseDrawerContent,
  BaseDrawerDescription,
  BaseDrawerFooter,
  BaseDrawerHeader,
  BaseDrawerOverlay,
  BaseDrawerPortal,
  BaseDrawerTitle,
  BaseDrawerTrigger,
};
