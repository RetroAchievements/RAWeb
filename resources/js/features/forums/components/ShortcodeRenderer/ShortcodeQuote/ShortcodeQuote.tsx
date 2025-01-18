import type { FC, ReactNode } from 'react';

interface ShortcodeQuoteProps {
  children: ReactNode;
}

export const ShortcodeQuote: FC<ShortcodeQuoteProps> = ({ children }) => {
  return <span className="quotedtext">{children}</span>;
};
