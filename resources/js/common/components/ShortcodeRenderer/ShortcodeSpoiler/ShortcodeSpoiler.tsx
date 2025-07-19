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

  const { isOpen, setIsOpen } = useAnimatedCollapse();

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

      <BaseCollapsibleContent forceMount>
        <div
          className={cn(
            'grid transition-[grid-template-rows] duration-300 ease-in-out',
            isOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
          )}
        >
          <div className="overflow-hidden">
            <div className="rounded-b-lg rounded-tr-lg bg-embed px-3 py-2">
              {stripLeadingWhitespaceFromChildren(children)}
            </div>
          </div>
        </div>
      </BaseCollapsibleContent>
    </BaseCollapsible>
  );
};
