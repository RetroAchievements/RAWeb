import * as motion from 'motion/react-m';
import { type FC, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { useAchievementGroupAnimation } from '@/common/hooks/useAchievementGroupAnimation';
import { cn } from '@/common/utils/cn';

interface GameSectionProps {
  gameCount: number;
  children: ReactNode;
  isInitiallyOpened: boolean;
  title: string;
}

export const GameListSection: FC<GameSectionProps> = ({
  gameCount,
  children,
  isInitiallyOpened,
  title,
}) => {
  const { t } = useTranslation();

  const { childContainerRef, contentRef, isInitialRender, isOpen, setIsOpen } =
    useAchievementGroupAnimation({ isInitiallyOpened });

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
            <span className="items-center text-sm font-medium">
              {title}
              <span className="ml-2 text-neutral-500">
                {'('}
                {t('{{val, number}} games', {
                  count: gameCount,
                  val: gameCount,
                })}
                {')'}
              </span>
            </span>

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
            className={cn(
              !isInitiallyOpened && isInitialRender.current ? 'h-0 overflow-hidden' : null,
            )}
          >
            <div className="relative pt-2.5">
              <ul ref={childContainerRef} className="zebra-list flex flex-col">
                {children}
              </ul>
            </div>
          </div>
        </BaseCollapsibleContent>
      </BaseCollapsible>
    </motion.li>
  );
};
