import * as React from 'react';

import { cn } from '@/utils/cn';

type BaseTextareaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement>;

export const baseTextareaClassNames = cn(
  'border-input bg-background ring-offset-background placeholder:text-muted-foreground',
  'focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm',
  'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-offset-1',
  'disabled:cursor-not-allowed disabled:opacity-50',
);

const BaseTextarea = React.forwardRef<HTMLTextAreaElement, BaseTextareaProps>(
  ({ className, ...props }, ref) => {
    return <textarea className={cn(baseTextareaClassNames, className)} ref={ref} {...props} />;
  },
);
BaseTextarea.displayName = 'BaseTextarea';

export { BaseTextarea };
