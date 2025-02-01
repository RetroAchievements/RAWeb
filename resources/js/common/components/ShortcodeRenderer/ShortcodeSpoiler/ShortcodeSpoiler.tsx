import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

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

  return (
    <BaseCollapsible>
      <BaseCollapsibleTrigger asChild>
        <BaseButton size="sm">{t('Spoiler')}</BaseButton>
      </BaseCollapsibleTrigger>

      <BaseCollapsibleContent className="rounded border border-dashed border-text-muted px-3 py-2">
        {stripLeadingWhitespaceFromChildren(children)}
      </BaseCollapsibleContent>
    </BaseCollapsible>
  );
};
