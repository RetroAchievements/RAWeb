import { createContext, type FC, type ReactNode, useContext, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

import { stripLeadingWhitespaceFromChildren } from '../../../utils/shortcodes/stripLeadingWhitespaceFromChildren';
import { BaseButton } from '../../+vendor/BaseButton';
import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '../../+vendor/BaseCollapsible';

/**
 * Tracks how many [quote] tags deep we are. A quote chain from repeated
 * replies nests [quote] blocks inside [quote] blocks, so we collapse
 * anything past the outermost (most recent) quote by default.
 */
const QuoteDepthContext = createContext(0);

interface ShortcodeQuoteProps {
  children: ReactNode;
}

export const ShortcodeQuote: FC<ShortcodeQuoteProps> = ({ children }) => {
  const { t } = useTranslation();

  const depth = useContext(QuoteDepthContext);
  const isNested = depth > 0;

  const [isOpen, setIsOpen] = useState(!isNested);

  const content = (
    <QuoteDepthContext.Provider value={depth + 1}>
      {stripLeadingWhitespaceFromChildren(children)}
    </QuoteDepthContext.Provider>
  );

  if (!isNested) {
    // div, not span: a nested quote can render block-level content.
    return <div className="quotedtext mb-3">{content}</div>;
  }

  return (
    <BaseCollapsible open={isOpen} onOpenChange={setIsOpen}>
      <BaseCollapsibleTrigger asChild>
        <BaseButton
          size="sm"
          className={cn('mb-1', isOpen ? 'rounded-b-none border-transparent bg-embed' : null)}
        >
          {t('Quote')}

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
            <div className="quotedtext mb-3">{content}</div>
          </div>
        </div>
      </BaseCollapsibleContent>
    </BaseCollapsible>
  );
};
