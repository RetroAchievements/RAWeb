import * as React from 'react';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

export type BaseInputProps = Omit<React.InputHTMLAttributes<HTMLInputElement>, 'placeholder'> & {
  placeholder?: TranslatedString;
};

const BaseInput = React.forwardRef<HTMLInputElement, BaseInputProps>(
  ({ className, type, ...props }, ref) => {
    return (
      <input
        type={type}
        className={cn(
          type === 'file' ? '' : 'h-10',
          'flex w-full rounded-md border px-3 text-sm light:border-neutral-200 light:bg-white',

          'file:my-1 file:mr-3 file:rounded file:border-0 file:bg-embed file:p-2 file:text-sm file:font-medium file:text-link file:outline file:outline-1 file:outline-neutral-700',
          'file:cursor-pointer hover:file:bg-embed-highlight hover:file:text-link-hover hover:file:outline-link-hover',
          'file:light:bg-white file:light:outline-link light:hover:file:bg-neutral-100 light:hover:file:text-link',

          'focus-visible:outline-none focus-visible:ring-1 light:ring-offset-white light:placeholder:text-neutral-500 light:focus-visible:ring-neutral-950',
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
