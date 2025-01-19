import type { FC, ReactNode } from 'react';

interface ShortcodeCodeProps {
  children: ReactNode;
}

export const ShortcodeCode: FC<ShortcodeCodeProps> = ({ children }) => {
  // Remove leading <br>s and empty strings until we find content.
  let isLeadingWhitespace = true;
  const processedChildren = (Array.isArray(children) ? children : [children]).filter((node) => {
    const isObjectNode = typeof node === 'object' && node !== null;
    const isEmptyObject = isObjectNode && !Object.keys(node as object).length;
    const isBRElement = isObjectNode && 'type' in node && node.type === 'br';
    const isEmptyString = typeof node === 'string' && node.trim() === '';

    isLeadingWhitespace = isLeadingWhitespace && (isEmptyObject || isBRElement || isEmptyString);

    // If it isn't leading whitespace or a BR element, it can stay.
    // Otherwise, it's filtered out.
    return !isLeadingWhitespace && !isBRElement;
  });

  return <span className="codetags font-mono">{processedChildren}</span>;
};
