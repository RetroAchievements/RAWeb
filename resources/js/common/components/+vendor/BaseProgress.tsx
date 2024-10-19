/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as ProgressPrimitive from '@radix-ui/react-progress';
import * as React from 'react';

import { cn } from '@/utils/cn';

interface BaseProgressSegment {
  value: number;
  className?: string;
}

type Props = React.ComponentPropsWithoutRef<typeof ProgressPrimitive.Root> & {
  segments: BaseProgressSegment[];
};

const BaseProgress = React.forwardRef<React.ElementRef<typeof ProgressPrimitive.Root>, Props>(
  ({ className, segments, max = 100, ...props }, ref) => {
    // Calculate total value of segments, capped at `max`.
    const totalValue = Math.min(
      segments.reduce((acc, segment) => acc + segment.value, 0),
      max,
    );

    return (
      <ProgressPrimitive.Root
        ref={ref}
        className={cn(
          'relative h-1.5 w-full space-x-px overflow-hidden rounded bg-zinc-950 light:bg-zinc-300',
          className,
        )}
        max={max}
        aria-valuemin={0}
        aria-valuenow={totalValue}
        {...props}
      >
        {segments.map((segment, index) => (
          <ProgressPrimitive.Indicator
            key={index}
            className={cn(
              'absolute h-full transition-all',
              segment.className ? segment.className : 'bg-neutral-500 light:bg-neutral-400',
            )}
            style={{
              width: `${(segment.value / max) * 100}%`,
              zIndex: segments.length - index,
              left: `${(segments.slice(0, index).reduce((acc, segment) => acc + segment.value, 0) / max) * 100}%`,
            }}
            aria-valuenow={segment.value}
            aria-valuemin={0}
            aria-valuemax={max}
            aria-label={`segment ${index + 1} progress`}
          />
        ))}
      </ProgressPrimitive.Root>
    );
  },
);

BaseProgress.displayName = 'BaseProgress';

export { BaseProgress };
