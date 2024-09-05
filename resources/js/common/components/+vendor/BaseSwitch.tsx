/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as SwitchPrimitives from '@radix-ui/react-switch';
import * as React from 'react';

import { cn } from '@/utils/cn';

const BaseSwitch = React.forwardRef<
  React.ElementRef<typeof SwitchPrimitives.Root>,
  React.ComponentPropsWithoutRef<typeof SwitchPrimitives.Root>
>(({ className, ...props }, ref) => (
  <SwitchPrimitives.Root
    className={cn(
      'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent',
      'transition-colors focus-visible:outline-none focus-visible:ring-2 light:focus-visible:ring-neutral-950',
      'focus-visible:ring-offset-2 light:focus-visible:ring-offset-white',
      'disabled:cursor-not-allowed disabled:opacity-50',
      'light:data-[state=checked]:bg-text light:data-[state=unchecked]:bg-neutral-200',
      'focus-visible:ring-neutral-300',
      'focus-visible:ring-offset-neutral-950 data-[state=checked]:bg-text',
      'data-[state=unchecked]:bg-neutral-700',
      className,
    )}
    {...props}
    ref={ref}
  >
    <SwitchPrimitives.Thumb
      className={cn(
        'pointer-events-none block h-5 w-5 rounded-full bg-neutral-50 shadow-lg ring-0 transition-transform light:bg-white',
        'data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0',
      )}
    />
  </SwitchPrimitives.Root>
));
BaseSwitch.displayName = 'BaseSwitch';

export { BaseSwitch };
