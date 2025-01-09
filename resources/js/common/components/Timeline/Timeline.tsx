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
}

const TimelineItem: FC<TimelineItemProps> = ({ label, children }) => {
  return (
    <>
      <div className="md:hidden">
        <div className="flex flex-col gap-2">
          <p className="text-gray-400 light:text-neutral-900">{label}</p>

          <div className="rounded-lg border border-neutral-700 bg-neutral-800/50 light:border-neutral-200 light:bg-neutral-50">
            {children}
          </div>
        </div>
      </div>

      <div className="relative hidden before:absolute before:bottom-[-3.5rem] before:left-32 before:top-5 before:w-px before:bg-neutral-700 last:before:hidden md:block">
        {/* Label on the left side. */}
        <div className="absolute left-0 top-2 w-24 text-right text-sm tracking-tight text-gray-400 light:text-neutral-900">
          {label}
        </div>

        {/* Dot marker. */}
        <div className="absolute left-32 top-5 ml-[-4.5px] size-2.5 rounded-full bg-neutral-700" />

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
