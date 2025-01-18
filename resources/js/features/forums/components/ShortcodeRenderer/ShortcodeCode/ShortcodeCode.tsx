import type { FC, ReactNode } from 'react';

interface ShortcodeCodeProps {
  children: ReactNode;
}

export const ShortcodeCode: FC<ShortcodeCodeProps> = ({ children }) => {
  if (Array.isArray(children)) {
    // Remove any leading newlines.
    const processedChildren = children.filter((child, index) => {
      if (index === 0 && typeof child === 'object' && 'type' in child && child.type === 'br') {
        return false;
      }

      return true;
    });

    return <span className="codetags font-mono">{processedChildren}</span>;
  }

  return <span className="codetags">{children}</span>;
};
