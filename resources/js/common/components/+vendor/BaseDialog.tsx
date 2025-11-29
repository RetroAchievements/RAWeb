/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as DialogPrimitive from '@radix-ui/react-dialog';
import * as React from 'react';
import { useTranslation } from 'react-i18next';
import { RxCross2 } from 'react-icons/rx';

import { cn } from '@/common/utils/cn';

const BaseDialog = DialogPrimitive.Root;

const BaseDialogTrigger = DialogPrimitive.Trigger;

const BaseDialogPortal = DialogPrimitive.Portal;

const BaseDialogClose = DialogPrimitive.Close;

const BaseDialogOverlay = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Overlay>,
  React.ComponentPropsWithoutRef<typeof DialogPrimitive.Overlay>
>(({ className, ...props }, ref) => (
  <DialogPrimitive.Overlay
    ref={ref}
    className={cn(
      'fixed inset-0 z-50 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out',
      'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
      className,
    )}
    {...props}
  />
));
BaseDialogOverlay.displayName = DialogPrimitive.Overlay.displayName;

const BaseDialogContent = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof DialogPrimitive.Content> & {
    shouldBlurBackdrop?: boolean;
    shouldShowCloseButton?: boolean;
  }
>(
  (
    { className, children, shouldBlurBackdrop = false, shouldShowCloseButton = true, ...props },
    ref,
  ) => {
    const { t } = useTranslation();

    const closeButtonRef = React.useRef<HTMLButtonElement>(null);
    const blockerTimeoutRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);

    /**
     * To band-aid a bug in Safari, we create a synthetic "dialog-hover-blocker"
     * element. We need to always be sure to clean that up on unmount so we
     * don't leak memory for every dialog we open.
     */
    React.useEffect(() => {
      return () => {
        if (blockerTimeoutRef.current) {
          clearTimeout(blockerTimeoutRef.current);
        }
        document.getElementById('dialog-hover-blocker')?.remove();
      };
    }, []);

    /**
     * On Safari mobile, tapping the dialog close button triggers a "sticky hover"
     * on elements underneath after the dialog closes (such as dropdown menus).
     * We handle touch separately by adding a temporary synthetic blocker element to
     * absorb the hover state, then programmatically trigger the close.
     *
     * If we don't do this, closing the dialog on mobile Safari can inadvertently
     * trigger elements z-indexed directly underneath the dialog close button.
     */
    const handleCloseTouchEnd = (e: React.TouchEvent) => {
      e.preventDefault(); // Prevent the synthetic click.

      // Only create one blocker at a time.
      if (!document.getElementById('dialog-hover-blocker')) {
        const blocker = document.createElement('div');
        blocker.id = 'dialog-hover-blocker';
        blocker.style.cssText = 'position:fixed;inset:0;z-index:9999;';
        document.body.appendChild(blocker);

        blockerTimeoutRef.current = setTimeout(() => {
          blocker.remove();
          blockerTimeoutRef.current = null;
        }, 300);
      }

      // Programmatically trigger the close via Radix.
      closeButtonRef.current?.click();
    };

    return (
      <BaseDialogPortal>
        <BaseDialogOverlay className={cn(shouldBlurBackdrop ? 'backdrop-blur' : '')} />

        <DialogPrimitive.Content
          ref={ref}
          className={cn(
            'fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg bg-embed light:bg-neutral-100',
            'translate-x-[-50%] translate-y-[-50%] gap-4 border border-neutral-600 p-6 shadow-lg duration-200 light:border-neutral-200',
            'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0',
            'data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
            'data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%]',
            'data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg',
            className,
          )}
          {...props}
        >
          {children}

          {shouldShowCloseButton ? (
            <DialogPrimitive.Close
              ref={closeButtonRef}
              className={cn(
                'ring-offset-background data-[state=open]:bg-accent data-[state=open]:text-muted-foreground',
                'absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100',
                'focus:outline-none focus:ring-offset-2 disabled:pointer-events-none',
                'text-link',
              )}
              onTouchEnd={handleCloseTouchEnd}
            >
              <RxCross2 className="size-4" />
              <span className="sr-only">{t('Close')}</span>
            </DialogPrimitive.Close>
          ) : null}
        </DialogPrimitive.Content>
      </BaseDialogPortal>
    );
  },
);
BaseDialogContent.displayName = DialogPrimitive.Content.displayName;

const BaseDialogHeader = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div className={cn('flex flex-col space-y-1.5 text-center sm:text-left', className)} {...props} />
);
BaseDialogHeader.displayName = 'DialogHeader';

const BaseDialogFooter = ({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) => (
  <div
    className={cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2', className)}
    {...props}
  />
);
BaseDialogFooter.displayName = 'DialogFooter';

const BaseDialogTitle = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Title>,
  React.ComponentPropsWithoutRef<typeof DialogPrimitive.Title>
>(({ className, ...props }, ref) => (
  <DialogPrimitive.Title
    ref={ref}
    className={cn('text-lg font-semibold leading-none tracking-tight', className)}
    {...props}
  />
));
BaseDialogTitle.displayName = DialogPrimitive.Title.displayName;

const BaseDialogDescription = React.forwardRef<
  React.ElementRef<typeof DialogPrimitive.Description>,
  React.ComponentPropsWithoutRef<typeof DialogPrimitive.Description>
>(({ className, ...props }, ref) => (
  <DialogPrimitive.Description
    ref={ref}
    className={cn('text-muted-foreground text-sm', className)}
    {...props}
  />
));
BaseDialogDescription.displayName = DialogPrimitive.Description.displayName;

export {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogOverlay,
  BaseDialogPortal,
  BaseDialogTitle,
  BaseDialogTrigger,
};
