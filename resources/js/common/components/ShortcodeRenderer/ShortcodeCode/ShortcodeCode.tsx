import type { FC, ReactNode } from 'react';

interface ShortcodeCodeProps {
  children: ReactNode;
}

export const ShortcodeCode: FC<ShortcodeCodeProps> = ({ children }) => {
  const processedChildren = filterLeadingWhitespace(children);

  return <span className="codetags mb-3 font-mono">{processedChildren}</span>;
};

/**
 * Removes leading <br>s and empty strings until actual content is found.
 */
function filterLeadingWhitespace(children: ReactNode): ReactNode[] {
  let isLeadingWhitespace = true;

  return (Array.isArray(children) ? children : [children]).filter((node) => {
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
}
