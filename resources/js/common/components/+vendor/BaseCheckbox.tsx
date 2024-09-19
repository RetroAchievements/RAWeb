/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import * as React from 'react';
import { HiOutlineCheck } from 'react-icons/hi';

import { cn } from '@/utils/cn';

const BaseCheckbox = React.forwardRef<
  React.ElementRef<typeof CheckboxPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof CheckboxPrimitive.Root>
>(({ className, ...props }, ref) => (
  <CheckboxPrimitive.Root
    ref={ref}
    className={cn(
      'peer h-4 w-4 shrink-0 rounded-sm border light:border-neutral-900',
      'focus-visible:outline-none focus-visible:ring-2 light:ring-offset-white light:focus-visible:ring-neutral-950',
      'focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      'border-neutral-600 light:data-[state=checked]:bg-text light:data-[state=checked]:text-neutral-50',
      'ring-offset-neutral-950 focus-visible:ring-neutral-300 data-[state=checked]:bg-neutral-700',
      'data-[state=checked]:border-neutral-50 data-[state=checked]:text-neutral-50',
      className,
    )}
    {...props}
  >
    <CheckboxPrimitive.Indicator className={cn('flex items-center justify-center text-current')}>
      <HiOutlineCheck className="h-4 w-4" />
    </CheckboxPrimitive.Indicator>
  </CheckboxPrimitive.Root>
));
BaseCheckbox.displayName = 'BaseCheckbox';

export { BaseCheckbox };
