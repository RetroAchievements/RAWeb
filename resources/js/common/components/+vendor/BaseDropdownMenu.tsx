/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as DropdownMenuPrimitive from '@radix-ui/react-dropdown-menu';
import * as React from 'react';
import { LuCheck, LuChevronRight, LuCircle } from 'react-icons/lu';

import { cn } from '@/utils/cn';

const BaseDropdownMenu = DropdownMenuPrimitive.Root;

const BaseDropdownMenuTrigger = DropdownMenuPrimitive.Trigger;

const BaseDropdownMenuGroup = DropdownMenuPrimitive.Group;

const BaseDropdownMenuPortal = DropdownMenuPrimitive.Portal;

const BaseDropdownMenuSub = DropdownMenuPrimitive.Sub;

const BaseDropdownMenuRadioGroup = DropdownMenuPrimitive.RadioGroup;

const BaseDropdownMenuSubTrigger = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.SubTrigger>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.SubTrigger> & {
    inset?: boolean;
  }
>(({ className, inset, children, ...props }, ref) => (
  <DropdownMenuPrimitive.SubTrigger
    ref={ref}
    className={cn(
      'flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none',
      'light:focus:bg-neutral-100 light:data-[state=open]:bg-neutral-100',
      'focus:bg-neutral-800 data-[state=open]:bg-neutral-800',
      inset && 'pl-8',
      className,
    )}
    {...props}
  >
    {children}
    <LuChevronRight className="ml-auto h-4 w-4" />
  </DropdownMenuPrimitive.SubTrigger>
));
BaseDropdownMenuSubTrigger.displayName = 'BaseDropdownMenuSubTrigger';

const BaseDropdownMenuSubContent = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.SubContent>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.SubContent>
>(({ className, ...props }, ref) => (
  <DropdownMenuPrimitive.SubContent
    ref={ref}
    className={cn(
      'z-50 min-w-[8rem] overflow-hidden rounded-md border',
      'p-1 shadow-lg light:border-neutral-200 light:bg-white light:text-neutral-950',
      'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0',
      'data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
      'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
      'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
      'border-neutral-800 bg-neutral-950 text-neutral-50',
      className,
    )}
    {...props}
  />
));
BaseDropdownMenuSubContent.displayName = 'BaseDropdownMenuSubContent';

const BaseDropdownMenuContent = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Content>
>(({ className, sideOffset = 4, ...props }, ref) => (
  <DropdownMenuPrimitive.Portal>
    <DropdownMenuPrimitive.Content
      ref={ref}
      sideOffset={sideOffset}
      className={cn(
        'z-50 min-w-[8rem] overflow-hidden rounded-md border',
        'p-1 light:border-neutral-200 light:bg-white light:text-neutral-950',
        'shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0',
        'data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
        'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
        'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        'border-neutral-800 bg-neutral-950 text-neutral-100',
        className,
      )}
      {...props}
    />
  </DropdownMenuPrimitive.Portal>
));
BaseDropdownMenuContent.displayName = 'BaseDropdownMenuContent';

const BaseDropdownMenuItem = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.Item>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Item> & {
    inset?: boolean;
  }
>(({ className, inset, ...props }, ref) => (
  <DropdownMenuPrimitive.Item
    ref={ref}
    className={cn(
      'relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm',
      'outline-none transition-colors light:focus:bg-neutral-100 light:focus:text-neutral-900',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      'focus:bg-neutral-800 focus:text-neutral-50',
      inset && 'pl-8',
      className,
    )}
    {...props}
  />
));
BaseDropdownMenuItem.displayName = 'BaseDropdownMenuItem';

const BaseDropdownMenuCheckboxItem = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.CheckboxItem>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.CheckboxItem>
>(({ className, children, checked, ...props }, ref) => (
  <DropdownMenuPrimitive.CheckboxItem
    ref={ref}
    className={cn(
      'relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2',
      'text-sm outline-none transition-colors light:focus:bg-neutral-100 light:focus:text-neutral-900',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      'focus:bg-neutral-800 focus:text-neutral-50',
      className,
    )}
    checked={checked}
    {...props}
  >
    <span className="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <DropdownMenuPrimitive.ItemIndicator>
        <LuCheck className="h-4 w-4" />
      </DropdownMenuPrimitive.ItemIndicator>
    </span>
    {children}
  </DropdownMenuPrimitive.CheckboxItem>
));
BaseDropdownMenuCheckboxItem.displayName = 'BaseDropdownMenuCheckboxItem';

const BaseDropdownMenuRadioItem = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.RadioItem>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.RadioItem>
>(({ className, children, ...props }, ref) => (
  <DropdownMenuPrimitive.RadioItem
    ref={ref}
    className={cn(
      'relative flex cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm',
      'outline-none transition-colors light:focus:bg-neutral-100 light:focus:text-neutral-900',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      'focus:bg-neutral-800 focus:text-neutral-50',
      className,
    )}
    {...props}
  >
    <span className="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <DropdownMenuPrimitive.ItemIndicator>
        <LuCircle className="h-2 w-2 fill-current" />
      </DropdownMenuPrimitive.ItemIndicator>
    </span>
    {children}
  </DropdownMenuPrimitive.RadioItem>
));
BaseDropdownMenuRadioItem.displayName = 'BaseDropdownMenuRadioItem';

const BaseDropdownMenuLabel = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.Label>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Label> & {
    inset?: boolean;
  }
>(({ className, inset, ...props }, ref) => (
  <DropdownMenuPrimitive.Label
    ref={ref}
    className={cn('px-2 py-1.5 text-sm font-semibold text-neutral-200', inset && 'pl-8', className)}
    {...props}
  />
));
BaseDropdownMenuLabel.displayName = 'BaseDropdownMenuLabel';

const BaseDropdownMenuSeparator = React.forwardRef<
  React.ElementRef<typeof DropdownMenuPrimitive.Separator>,
  React.ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Separator>
>(({ className, ...props }, ref) => (
  <DropdownMenuPrimitive.Separator
    ref={ref}
    className={cn('-mx-1 my-1 h-px bg-neutral-800 light:bg-neutral-100', className)}
    {...props}
  />
));
BaseDropdownMenuSeparator.displayName = 'BaseDropdownMenuSeparator';

const BaseDropdownMenuShortcut = ({
  className,
  ...props
}: React.HTMLAttributes<HTMLSpanElement>) => {
  return (
    <span className={cn('ml-auto text-xs tracking-widest opacity-60', className)} {...props} />
  );
};
BaseDropdownMenuShortcut.displayName = 'BaseDropdownMenuShortcut';

export {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuGroup,
  BaseDropdownMenuItem,
  BaseDropdownMenuLabel,
  BaseDropdownMenuPortal,
  BaseDropdownMenuRadioGroup,
  BaseDropdownMenuRadioItem,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuShortcut,
  BaseDropdownMenuSub,
  BaseDropdownMenuSubContent,
  BaseDropdownMenuSubTrigger,
  BaseDropdownMenuTrigger,
};
