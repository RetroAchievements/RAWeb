import { type FC, memo } from 'react';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

interface GameMetadataRowProps {
  rowHeading: string;
  elements: Array<{ label: string; href?: string }>;
}

export const GameMetadataRow: FC<GameMetadataRowProps> = memo(({ rowHeading, elements }) => {
  const { auth } = usePageProps();

  if (!elements?.length) {
    return null;
  }

  const locale = auth?.user.locale?.replace('_', '-') ?? 'en-US'; // Use Intl locale code format.

  const formattedElements = elements.map((item, index) =>
    item.href ? (
      <InertiaLink key={`link-${index}`} href={item.href} prefetch="desktop-hover-only">
        {item.label}
      </InertiaLink>
    ) : (
      <span key={`text-${index}`}>{item.label}</span>
    ),
  );

  const listFormatter = new Intl.ListFormat(locale, {
    style: 'narrow',
    type: 'conjunction',
  });

  const labels = elements.map((item) => item.label);
  const parts = listFormatter.formatToParts(labels);

  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableCell className="whitespace-nowrap text-right align-top">{rowHeading}</BaseTableCell>
      <BaseTableCell>
        {parts.map((part, index) => {
          if (part.type === 'element') {
            // Return the actual React element at this position.
            const elementIndex = parts.slice(0, index).filter((p) => p.type === 'element').length;

            return formattedElements[elementIndex];
          }

          // Return the separator literal.
          return <span key={`separator-${index}`}>{part.value}</span>;
        })}
      </BaseTableCell>
    </BaseTableRow>
  );
});
