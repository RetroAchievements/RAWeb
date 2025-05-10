import { AnimatePresence } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC } from 'react';
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

import { DeleteTopicButton } from './DeleteTopicButton';
import { ToggleLockTopicButton } from './ToggleLockTopicButton';
import { TopicManageForm } from './TopicManageForm';
import { TopicOptionsForm } from './TopicOptionsForm';

export const TopicOptions: FC = () => {
  const { can } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();

  return (
    <BaseCollapsible open={isOpen} onOpenChange={setIsOpen}>
      <BaseCollapsibleTrigger asChild>
        <BaseButton
          size="sm"
          className={cn(isOpen ? 'rounded-b-none border-transparent bg-embed' : null)}
        >
          {t('Options')}

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
              <div
                ref={contentRef}
                className="flex flex-col gap-8 rounded-b-lg rounded-tr-lg bg-embed p-4"
              >
                <TopicOptionsForm />

                {can.manageForumTopics ? <TopicManageForm /> : null}

                <div className="flex gap-2">
                  {can.lockForumTopic ? <ToggleLockTopicButton /> : null}
                  {can.deleteForumTopic ? <DeleteTopicButton /> : null}
                </div>
              </div>
            </m.div>
          </BaseCollapsibleContent>
        ) : null}
      </AnimatePresence>
    </BaseCollapsible>
  );
};
