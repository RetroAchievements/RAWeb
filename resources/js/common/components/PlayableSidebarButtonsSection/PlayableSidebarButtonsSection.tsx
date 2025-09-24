import type { FC, ReactNode } from 'react';

import type { TranslatedString } from '@/types/i18next';

interface PlayableSidebarButtonsSectionProps {
  children: ReactNode;
  headingLabel: TranslatedString;
}

export const PlayableSidebarButtonsSection: FC<PlayableSidebarButtonsSectionProps> = ({
  children,
  headingLabel,
}) => {
  // If there aren't any buttons or anchors in the children tree,
  // then don't render the section.
  const hasButtonOrAnchor = containsButtonOrAnchor(children);
  if (!hasButtonOrAnchor) {
    return null;
  }

  return (
    <div className="flex flex-col gap-1">
      <p className="text-xs text-neutral-300 light:text-neutral-800">{headingLabel}</p>

      {children}
    </div>
  );
};

/**
 * Recursively checks if a React element tree contains any buttons or anchors.
 */
function containsButtonOrAnchor(node: ReactNode): boolean {
  if (!node) {
    return false;
  }

  if (Array.isArray(node)) {
    return node.some(containsButtonOrAnchor);
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- this is fully dynamic
  const dynamicProps = (node as any).props as any;

  // Check for interactive elements by their props.
  if (dynamicProps?.href || dynamicProps?.onClick) {
    return true;
  }

  // Recursively check children.
  return containsButtonOrAnchor(dynamicProps?.children);
}
