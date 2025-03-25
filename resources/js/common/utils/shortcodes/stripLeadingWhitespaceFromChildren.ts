import type { ReactNode } from 'react';

export function stripLeadingWhitespaceFromChildren(children: ReactNode) {
  // Remove leading <br>s and empty strings until we find content.
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
