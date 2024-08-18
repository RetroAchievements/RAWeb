import * as React from 'react';

import { cn } from '@/utils/cn';

export type BaseInputProps = React.InputHTMLAttributes<HTMLInputElement>;

const BaseInput = React.forwardRef<HTMLInputElement, BaseInputProps>(
  ({ className, type, ...props }, ref) => {
    return (
      <input
        type={type}
        className={cn(
          'flex h-10 w-full rounded-md border px-3 py-2 text-sm light:border-neutral-200 light:bg-white',
          'file:mt-1 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-link light:ring-offset-white',
          'focus-visible:outline-none focus-visible:ring-2 light:placeholder:text-neutral-500 light:focus-visible:ring-neutral-950',
          'border-neutral-800 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
          'bg-neutral-950 ring-offset-neutral-950 placeholder:text-neutral-400 focus-visible:ring-neutral-300',
          className,
        )}
        ref={ref}
        {...props}
      />
    );
  },
);
BaseInput.displayName = 'BaseInput';

export { BaseInput };
