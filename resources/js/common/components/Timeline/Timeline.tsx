import type { FC, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

interface TimelineProps {
  children: ReactNode;
}

const Timeline: FC<TimelineProps> = ({ children }) => {
  return (
    <div className="relative">
      <div className="flex flex-col gap-8">{children}</div>
    </div>
  );
};

interface TimelineItemProps {
  label: string;
  children: ReactNode;

  moreAfter?: ReactNode;
  moreBefore?: ReactNode;
}

const TimelineItem: FC<TimelineItemProps> = ({ label, children, moreAfter, moreBefore }) => {
  return (
    <>
      <div className="md:hidden">
        <div className="flex flex-col gap-2">
          {moreBefore ? <p className="text-xs">{moreBefore}</p> : null}

          <p className="text-gray-400 light:text-neutral-900">{label}</p>

          <div className="rounded-lg border border-neutral-700 bg-neutral-800/50 light:border-neutral-200 light:bg-neutral-50">
            {children}
          </div>

          {moreAfter ? <p className="text-xs">{moreAfter}</p> : null}
        </div>
      </div>

      <div
        className={cn(
          'relative hidden before:absolute before:top-5 before:-bottom-14 before:left-32 before:w-px before:bg-neutral-700 last:before:hidden md:block',
          moreBefore &&
            'mt-16 after:absolute after:-top-16 after:left-32 after:h-21 after:w-px after:bg-[repeating-linear-gradient(to_top,var(--color-neutral-600)_0_5px,transparent_5px_9px)]',
          moreAfter &&
            'mb-16 before:-bottom-16 before:bg-transparent before:bg-[repeating-linear-gradient(var(--color-neutral-600)_0_5px,transparent_5px_9px)] last:before:block',
        )}
      >
        {/* Label on the left side. */}
        <div className="absolute top-2 left-0 w-24 text-right text-sm tracking-tight text-gray-400 light:text-neutral-900">
          {label}
        </div>

        {moreBefore ? (
          <div className="absolute -top-12 left-0 w-24 text-right text-xs">{moreBefore}</div>
        ) : null}

        {moreAfter ? (
          <div className="absolute -bottom-12 left-0 w-24 text-right text-xs">{moreAfter}</div>
        ) : null}

        {/* Dot marker. */}
        <div className="absolute top-5 left-32 -ml-1.25 size-2.75 rounded-full bg-neutral-700" />

        {/* Content container. */}
        <div
          className={cn(
            'ml-44 rounded-lg border border-neutral-700 bg-neutral-800/50 shadow-lg shadow-neutral-800/50',
            'light:border-neutral-200 light:bg-neutral-50 light:shadow-neutral-200',
          )}
        >
          {children}
        </div>
      </div>
    </>
  );
};

export { Timeline, TimelineItem };
