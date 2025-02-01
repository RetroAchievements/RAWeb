import type { FC, ReactNode } from 'react';

import { stripLeadingWhitespaceFromChildren } from '../../../utils/shortcodes/stripLeadingWhitespaceFromChildren';

interface ShortcodeQuoteProps {
  children: ReactNode;
}

export const ShortcodeQuote: FC<ShortcodeQuoteProps> = ({ children }) => {
  return <span className="quotedtext mb-3">{stripLeadingWhitespaceFromChildren(children)}</span>;
};
