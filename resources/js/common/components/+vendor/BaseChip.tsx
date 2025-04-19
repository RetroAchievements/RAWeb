import { cva } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

export const baseChipVariants = cva(
  [
    'light:border light:border-neutral-300',
    'flex max-w-fit items-center gap-1 rounded-full bg-zinc-950/60 px-2.5 py-0.5 text-xs light:bg-neutral-50',
  ],
  {
    variants: {
      variant: {
        default: '',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

const BaseChip = React.forwardRef<HTMLSpanElement, React.HTMLAttributes<HTMLSpanElement>>(
  ({ className, ...props }, ref) => (
    <span
      ref={ref}
      className={cn(baseChipVariants({ variant: 'default' }), className)}
      {...props}
    />
  ),
);
BaseChip.displayName = 'BaseChip';

export { BaseChip };
