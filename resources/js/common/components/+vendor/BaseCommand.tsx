/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import type { DialogProps } from '@radix-ui/react-dialog';
import { Command as CommandPrimitive } from 'cmdk';
import * as React from 'react';
import { RxMagnifyingGlass } from 'react-icons/rx';

import { cn } from '@/utils/cn';

import { BaseDialog, BaseDialogContent } from './BaseDialog';

const BaseCommand = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive>
>(({ className, ...props }, ref) => (
  <CommandPrimitive
    ref={ref}
    className={cn(
      'text-popover-foreground flex h-full w-full flex-col overflow-hidden rounded-md bg-neutral-950',
      className,
    )}
    {...props}
  />
));
BaseCommand.displayName = CommandPrimitive.displayName;

type BaseCommandDialogProps = DialogProps;

const BaseCommandDialog = ({ children, ...props }: BaseCommandDialogProps) => {
  return (
    <BaseDialog {...props}>
      <BaseDialogContent className="overflow-hidden p-0">
        <BaseCommand className="[&_[cmdk-group-heading]]:text-muted-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 [&_[cmdk-group]]:px-2 [&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5 [&_[cmdk-input]]:h-12 [&_[cmdk-item]]:px-2 [&_[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5">
          {children}
        </BaseCommand>
      </BaseDialogContent>
    </BaseDialog>
  );
};

const BaseCommandInput = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.Input>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.Input>
>(({ className, ...props }, ref) => (
  // eslint-disable-next-line react/no-unknown-property -- cmdk-input-wrapper is valid
  <div className="flex items-center border-b border-embed-highlight px-3" cmdk-input-wrapper="">
    <RxMagnifyingGlass className="mr-2 h-5 w-5 shrink-0 text-neutral-200 opacity-50 light:text-neutral-900" />
    <CommandPrimitive.Input
      ref={ref}
      className={cn(
        'placeholder:text-muted-foreground flex h-10 w-full rounded-md border-none bg-transparent py-3',
        'text-sm outline-none disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    />
  </div>
));
BaseCommandInput.displayName = CommandPrimitive.Input.displayName;

const BaseCommandList = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.List>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.List>
>(({ className, ...props }, ref) => (
  <CommandPrimitive.List
    ref={ref}
    className={cn('max-h-[300px] overflow-y-auto overflow-x-hidden', className)}
    {...props}
  />
));
BaseCommandList.displayName = CommandPrimitive.List.displayName;

const BaseCommandEmpty = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.Empty>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.Empty>
>((props, ref) => (
  <CommandPrimitive.Empty ref={ref} className="py-6 text-center text-sm" {...props} />
));
BaseCommandEmpty.displayName = CommandPrimitive.Empty.displayName;

const BaseCommandGroup = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.Group>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.Group>
>(({ className, ...props }, ref) => (
  <CommandPrimitive.Group
    ref={ref}
    className={cn(
      'text-foreground [&_[cmdk-group-heading]]:text-muted-foreground overflow-hidden p-1 [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-medium',
      className,
    )}
    {...props}
  />
));
BaseCommandGroup.displayName = CommandPrimitive.Group.displayName;

const BaseCommandSeparator = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.Separator>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.Separator>
>(({ className, ...props }, ref) => (
  <CommandPrimitive.Separator
    ref={ref}
    className={cn('bg-border -mx-1 h-px', className)}
    {...props}
  />
));
BaseCommandSeparator.displayName = CommandPrimitive.Separator.displayName;

const BaseCommandItem = React.forwardRef<
  React.ElementRef<typeof CommandPrimitive.Item>,
  React.ComponentPropsWithoutRef<typeof CommandPrimitive.Item>
>(({ className, ...props }, ref) => (
  <CommandPrimitive.Item
    ref={ref}
    className={cn(
      'data-[selected=true]:bg-accent data-[selected=true]:text-accent-foreground relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none data-[disabled=true]:pointer-events-none data-[disabled=true]:opacity-50',
      className,
    )}
    {...props}
  />
));
BaseCommandItem.displayName = CommandPrimitive.Item.displayName;

const BaseCommandShortcut = ({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) => {
  return (
    <span
      className={cn('text-muted-foreground ml-auto text-xs tracking-widest', className)}
      {...props}
    />
  );
};
BaseCommandShortcut.displayName = 'CommandShortcut';

export {
  BaseCommand,
  BaseCommandDialog,
  BaseCommandEmpty,
  BaseCommandGroup,
  BaseCommandInput,
  BaseCommandItem,
  BaseCommandList,
  BaseCommandSeparator,
  BaseCommandShortcut,
};
