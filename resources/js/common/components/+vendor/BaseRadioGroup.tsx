/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { RadioGroup as RadioGroupPrimitive } from 'radix-ui';
import type * as React from 'react';
import { LuCircle } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

function BaseRadioGroup({
  className,
  ...props
}: React.ComponentProps<typeof RadioGroupPrimitive.Root>) {
  return (
    <RadioGroupPrimitive.Root
      data-slot="radio-group"
      className={cn('grid gap-3', className)}
      {...props}
    />
  );
}

function BaseRadioGroupItem({
  className,
  ...props
}: React.ComponentProps<typeof RadioGroupPrimitive.Item>) {
  return (
    <RadioGroupPrimitive.Item
      data-slot="radio-group-item"
      className={cn(
        'aspect-square size-4 shrink-0 rounded-full border text-neutral-50 light:text-neutral-900',
        'border-neutral-500 data-[state=checked]:border-neutral-200 light:border-neutral-900',
        'outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2',
        'ring-offset-neutral-950 focus-visible:ring-neutral-300',
        'light:ring-offset-white light:focus-visible:ring-neutral-950',
        'disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
      {...props}
    >
      <RadioGroupPrimitive.Indicator
        data-slot="radio-group-indicator"
        className="relative flex items-center justify-center"
      >
        <LuCircle className="absolute top-1/2 left-1/2 size-2 -translate-x-1/2 -translate-y-1/2 fill-current" />
      </RadioGroupPrimitive.Indicator>
    </RadioGroupPrimitive.Item>
  );
}

export { BaseRadioGroup, BaseRadioGroupItem };
