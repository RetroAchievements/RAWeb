import type { FC, ReactNode } from 'react';

interface ShortcodeQuoteProps {
  children: ReactNode;
}

export const ShortcodeQuote: FC<ShortcodeQuoteProps> = ({ children }) => {
  // Remove leading <br>s and empty strings until we find content.
  let isLeadingWhitespace = true;
  const processedChildren = (Array.isArray(children) ? children : [children]).filter((node) => {
    if (isLeadingWhitespace) {
      const isObjectNode = typeof node === 'object' && node !== null;
      const isEmptyObject = isObjectNode && !Object.keys(node as object).length;
      const isBRElement = isObjectNode && 'type' in node && node.type === 'br';
      const isEmptyString = typeof node === 'string' && node.trim() === '';

      isLeadingWhitespace = isEmptyObject || isBRElement || isEmptyString;
    }

    // If it isn't leading whitespace, it can stay.
    // Otherwise, it's filtered out.
    return !isLeadingWhitespace;
  });

  return <span className="quotedtext mb-3">{processedChildren}</span>;
};
