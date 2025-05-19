import { AnimatePresence } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { useAnimatedCollapse } from '@/common/hooks/useAnimatedCollapse';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { HashesList } from './HashesList';

export const OtherHashesSection: FC = memo(() => {
  const { incompatibleHashes, untestedHashes, patchRequiredHashes } =
    usePageProps<App.Platform.Data.GameHashesPageProps>();

  const hasOtherHashes =
    incompatibleHashes?.length || untestedHashes?.length || patchRequiredHashes?.length;

  const { t } = useTranslation();

  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();

  if (!hasOtherHashes) return null;

  return (
    <BaseCollapsible open={isOpen} onOpenChange={setIsOpen}>
      <BaseCollapsibleTrigger asChild>
        <BaseButton
          size="sm"
          className={cn(isOpen ? 'rounded-b-none border-transparent bg-embed' : null)}
        >
          {t('Other Known Hashes')}

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
              <div ref={contentRef} className="bg-embed p-4">
                {patchRequiredHashes?.length ? (
                  <div className="flex flex-col gap-1">
                    <p>{t('These game file hashes require a patch to be compatible.')}</p>

                    <HashesList hashes={patchRequiredHashes} />
                  </div>
                ) : null}

                {untestedHashes?.length ? (
                  <div className="flex flex-col gap-1">
                    <p>
                      {t(
                        'These game file hashes are recognized, but it is unknown whether or not they are compatible.',
                      )}
                    </p>

                    <HashesList hashes={untestedHashes} />
                  </div>
                ) : null}

                {incompatibleHashes?.length ? (
                  <div className="flex flex-col gap-1">
                    <p>{t('These game file hashes are known to be incompatible.')}</p>

                    <HashesList hashes={incompatibleHashes} />
                  </div>
                ) : null}
              </div>
            </m.div>
          </BaseCollapsibleContent>
        ) : null}
      </AnimatePresence>
    </BaseCollapsible>
  );
});
