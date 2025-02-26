import * as motion from 'motion/react-m';
import { type FC, type ReactNode } from 'react';
import { LuChevronDown } from 'react-icons/lu';

import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { cn } from '@/common/utils/cn';

import { useEventAchievementSectionAnimation } from './useEventAchievementSectionAnimation';

interface EventAchievementSectionProps {
  children: ReactNode;
  title: string;
}

export const EventAchievementSection: FC<EventAchievementSectionProps> = ({ children, title }) => {
  const { childContainerRef, contentRef, isInitialRender, isOpen, setIsOpen } =
    useEventAchievementSectionAnimation({ isInitiallyOpened: true });

  return (
    <motion.li
      className="flex flex-col gap-2.5"
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 10 }}
      transition={{
        duration: 0.12,
        delay: 0.03, // Tiny delay to let previous items finish exiting.
      }}
    >
      <BaseCollapsible open={isOpen} onOpenChange={setIsOpen} className="w-full">
        <div className="flex items-center rounded bg-embed px-3 py-1.5">
          <BaseCollapsibleTrigger className="flex flex-1 items-center justify-between text-neutral-300 light:text-neutral-700">
            <span className="text-sm font-medium">{title}</span>

            <LuChevronDown
              className={cn(
                'size-4 transition-transform duration-300',
                isOpen ? 'rotate-180' : 'rotate-0',
              )}
            />
          </BaseCollapsibleTrigger>
        </div>

        <BaseCollapsibleContent forceMount>
          <div
            ref={contentRef}
            className={cn(!isOpen && isInitialRender.current && 'h-0 overflow-hidden')}
          >
            <div className="pt-2.5">
              <ul ref={childContainerRef} className="flex flex-col gap-2.5">
                {children}
              </ul>
            </div>
          </div>
        </BaseCollapsibleContent>
      </BaseCollapsible>
    </motion.li>
  );
};
