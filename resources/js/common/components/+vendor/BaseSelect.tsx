/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as SelectPrimitive from '@radix-ui/react-select';
import * as React from 'react';
import type { IconType } from 'react-icons/lib';
import { LuCheck, LuChevronDown } from 'react-icons/lu';

import { cn } from '@/utils/cn';

const BaseSelect = SelectPrimitive.Root;

const BaseSelectGroup = SelectPrimitive.Group;

const BaseSelectValue = SelectPrimitive.Value;

const BaseSelectTrigger = React.forwardRef<
  React.ElementRef<typeof SelectPrimitive.Trigger>,
  React.ComponentPropsWithoutRef<typeof SelectPrimitive.Trigger>
>(({ className, children, ...props }, ref) => (
  <SelectPrimitive.Trigger
    ref={ref}
    className={cn(
      'flex h-10 w-full items-center justify-between rounded-md border light:border-neutral-200',
      'px-3 py-2 text-sm light:bg-white light:ring-offset-white light:placeholder:text-neutral-500',
      'focus:outline-none focus:ring-2 focus:ring-offset-2 light:focus:ring-neutral-950',
      'disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
      'border-neutral-800 bg-neutral-950 text-menu-link ring-offset-neutral-950 placeholder:text-neutral-400',
      'focus:ring-neutral-300',
      className,
    )}
    {...props}
  >
    {children}
    <SelectPrimitive.Icon asChild>
      <LuChevronDown className="h-4 w-4 opacity-50" />
    </SelectPrimitive.Icon>
  </SelectPrimitive.Trigger>
));
BaseSelectTrigger.displayName = 'BaseSelectTrigger';

const BaseSelectContent = React.forwardRef<
  React.ElementRef<typeof SelectPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof SelectPrimitive.Content>
>(({ className, children, position = 'popper', ...props }, ref) => (
  <SelectPrimitive.Portal>
    <SelectPrimitive.Content
      ref={ref}
      className={cn(
        'relative z-50 max-h-96 min-w-[8rem] overflow-hidden rounded-md border light:border-neutral-200 light:bg-white',
        'shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out light:text-neutral-950',
        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95',
        'data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
        'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        'border-neutral-800 bg-neutral-950 text-neutral-50',

        position === 'popper' &&
          'data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1',

        className,
      )}
      position={position}
      {...props}
    >
      <SelectPrimitive.Viewport
        className={cn(
          'p-1',
          position === 'popper' &&
            'h-[var(--radix-select-trigger-height)] w-full min-w-[var(--radix-select-trigger-width)]',
        )}
      >
        {children}
      </SelectPrimitive.Viewport>
    </SelectPrimitive.Content>
  </SelectPrimitive.Portal>
));
BaseSelectContent.displayName = 'BaseSelectContent';

const BaseSelectLabel = React.forwardRef<
  React.ElementRef<typeof SelectPrimitive.Label>,
  React.ComponentPropsWithoutRef<typeof SelectPrimitive.Label>
>(({ className, ...props }, ref) => (
  <SelectPrimitive.Label
    ref={ref}
    className={cn('py-1.5 pl-8 pr-2 text-sm font-semibold', className)}
    {...props}
  />
));
BaseSelectLabel.displayName = 'BaseSelectLabel';

const BaseSelectItem = React.forwardRef<
  React.ElementRef<typeof SelectPrimitive.Item>,
  React.ComponentPropsWithoutRef<typeof SelectPrimitive.Item> & { Icon?: IconType }
>(({ className, children, Icon, ...props }, ref) => (
  <SelectPrimitive.Item
    ref={ref}
    className={cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50 light:focus:bg-neutral-100 light:focus:text-neutral-900',
      'focus:bg-neutral-800 focus:text-neutral-50',
      className,
    )}
    {...props}
  >
    <span className="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <SelectPrimitive.ItemIndicator>
        <LuCheck className="h-4 w-4" />
      </SelectPrimitive.ItemIndicator>
    </span>

    <SelectPrimitive.ItemText>
      <span className="flex items-center gap-2">
        {Icon ? <Icon className="h-4 w-4" /> : null}
        {children}
      </span>
    </SelectPrimitive.ItemText>
  </SelectPrimitive.Item>
));
BaseSelectItem.displayName = 'BaseSelectItem';

const BaseSelectSeparator = React.forwardRef<
  React.ElementRef<typeof SelectPrimitive.Separator>,
  React.ComponentPropsWithoutRef<typeof SelectPrimitive.Separator>
>(({ className, ...props }, ref) => (
  <SelectPrimitive.Separator
    ref={ref}
    className={cn('-mx-1 my-1 h-px bg-neutral-100 dark:bg-neutral-800', className)}
    {...props}
  />
));
BaseSelectSeparator.displayName = 'BaseSelectSeparator';

export {
  BaseSelect,
  BaseSelectContent,
  BaseSelectGroup,
  BaseSelectItem,
  BaseSelectLabel,
  BaseSelectSeparator,
  BaseSelectTrigger,
  BaseSelectValue,
};
