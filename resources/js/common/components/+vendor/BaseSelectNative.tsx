import * as React from 'react';
import { LuChevronDown } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

export interface BaseSelectPropsNative extends React.SelectHTMLAttributes<HTMLSelectElement> {
  children: React.ReactNode;
}

const BaseSelectNative = React.forwardRef<HTMLSelectElement, BaseSelectPropsNative>(
  ({ className, children, ...props }, ref) => {
    return (
      <div className="relative">
        <select
          className={cn(
            'peer inline-flex h-10 w-full cursor-pointer appearance-none items-center rounded-md',
            'border border-neutral-800 bg-neutral-950 text-sm text-menu-link',
            'focus-visible:border-ring transition-colors focus-visible:outline-none focus-visible:ring-1',
            'focus-visible:ring-neutral-300 disabled:pointer-events-none disabled:cursor-not-allowed',
            'has-[option[disabled]:checked]:text-muted-foreground disabled:opacity-50',
            'light:border-neutral-200 light:bg-white light:focus:ring-neutral-950',

            props.multiple
              ? '[&_option:checked]:bg-accent py-1 [&>*]:px-3 [&>*]:py-1'
              : 'h-9 pe-8 ps-3',

            className,
          )}
          ref={ref}
          {...props}
        >
          {children}
        </select>
        {!props.multiple && (
          <span className="text-muted-foreground/80 pointer-events-none absolute inset-y-0 end-0 flex h-full w-9 items-center justify-center peer-disabled:opacity-50">
            <LuChevronDown
              className="size-4 text-neutral-300 opacity-50 light:text-neutral-800"
              strokeWidth={2}
              aria-hidden="true"
            />
          </span>
        )}
      </div>
    );
  },
);
BaseSelectNative.displayName = 'BaseSelectNative';

export { BaseSelectNative };
