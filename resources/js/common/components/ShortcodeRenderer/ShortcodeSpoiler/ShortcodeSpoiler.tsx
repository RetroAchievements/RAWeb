import { AnimatePresence } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { useAnimatedCollapse } from '@/common/hooks/useAnimatedCollapse';
import { cn } from '@/common/utils/cn';

import { stripLeadingWhitespaceFromChildren } from '../../../utils/shortcodes/stripLeadingWhitespaceFromChildren';
import { BaseButton } from '../../+vendor/BaseButton';
import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '../../+vendor/BaseCollapsible';

interface ShortcodeSpoilerProps {
  children: ReactNode;
}

export const ShortcodeSpoiler: FC<ShortcodeSpoilerProps> = ({ children }) => {
  const { t } = useTranslation();

  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();

  return (
    <BaseCollapsible open={isOpen} onOpenChange={setIsOpen}>
      <BaseCollapsibleTrigger asChild>
        <BaseButton
          size="sm"
          className={cn(isOpen ? 'rounded-b-none border-transparent bg-embed' : null)}
        >
          {t('Spoiler')}

          <LuChevronDown
            className={cn(
              'ml-1 size-4 transition-transform duration-300',
              isOpen ? 'rotate-180' : 'rotate-0',
            )}
          />
        </BaseButton>
      </BaseCollapsibleTrigger>

      <AnimatePresence initial={false}>
        {isOpen ? (
          <BaseCollapsibleContent forceMount asChild>
            <m.div
              initial={{ height: 0 }}
              animate={{ height: contentHeight }}
              exit={{ height: 0 }}
              transition={{
                duration: 0.3,
                ease: [0.4, 0, 0.2, 1], // Custom easing curve for natural motion.
              }}
              className="overflow-hidden"
            >
              <div ref={contentRef} className="rounded-b-lg rounded-tr-lg bg-embed px-3 py-2">
                {stripLeadingWhitespaceFromChildren(children)}
              </div>
            </m.div>
          </BaseCollapsibleContent>
        ) : null}
      </AnimatePresence>
    </BaseCollapsible>
  );
};
