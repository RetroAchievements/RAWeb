import { AnimatePresence, useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, type ReactNode, useState } from 'react';
import type { IconType } from 'react-icons/lib';
import { LuChevronDown } from 'react-icons/lu';

import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { useAnimatedCollapse } from '@/common/hooks/useAnimatedCollapse';
import { cn } from '@/common/utils/cn';

interface TicketCollapsibleSectionProps {
  children: ReactNode;
  Icon: IconType;
  label: ReactNode;

  className?: string;
  contentClassName?: string;
  iconClassName?: string;
}

export const TicketCollapsibleSection: FC<TicketCollapsibleSectionProps> = ({
  children,
  className,
  contentClassName,
  Icon,
  iconClassName,
  label,
}) => {
  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();
  const [collapseHeight, setCollapseHeight] = useState(0);

  const doesPreferReducedMotion = useReducedMotion();

  const handleExpandedChange = (isExpanding: boolean) => {
    if (!isExpanding) {
      setCollapseHeight(Math.min(contentRef.current!.offsetHeight, window.innerHeight));
    }

    setIsOpen(isExpanding);
  };

  const expandHeight = isOpen ? Math.min(contentHeight, window.innerHeight) : 0;
  const expandTransition = doesPreferReducedMotion
    ? { height: { duration: 0 }, opacity: { duration: 0.15 } }
    : { duration: 0.25, ease: [0.23, 1, 0.32, 1] as const };

  return (
    <BaseCollapsible open={isOpen} onOpenChange={handleExpandedChange} className={className}>
      <BaseCollapsibleTrigger className="flex w-full items-center gap-3 px-3 py-2 text-left text-link hover:text-link-hover">
        <Icon
          className={cn('size-4 flex-none text-neutral-400', iconClassName)}
          aria-hidden="true"
        />

        <span className="grow">{label}</span>

        <LuChevronDown
          className={cn(
            'size-4 flex-none text-neutral-400 transition-transform duration-200',
            isOpen ? 'rotate-180' : 'rotate-0',
          )}
          aria-hidden="true"
        />
      </BaseCollapsibleTrigger>

      <AnimatePresence initial={false} custom={collapseHeight}>
        {isOpen ? (
          <BaseCollapsibleContent forceMount asChild>
            <m.div
              data-testid="collapsible-section-content"
              custom={collapseHeight}
              variants={{
                collapsed: (collapsedHeight: number) => ({
                  height: [collapsedHeight, 0],
                  opacity: 0,
                }),
              }}
              initial={{ height: 0, opacity: 0 }}
              animate={{
                height: expandHeight,
                opacity: 1,
                ...(expandHeight > 0 && { transitionEnd: { height: 'auto' } }),
              }}
              exit="collapsed"
              transition={expandTransition}
              className="overflow-hidden border-t border-neutral-700 light:border-neutral-200"
            >
              <div ref={contentRef} className={cn('flex flex-col gap-4 p-3', contentClassName)}>
                {children}
              </div>
            </m.div>
          </BaseCollapsibleContent>
        ) : null}
      </AnimatePresence>
    </BaseCollapsible>
  );
};
