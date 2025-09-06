import { type FC, memo } from 'react';
import { route } from 'ziggy-js';

import { BaseTableCell, BaseTableHead, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

interface GameMetadataRowProps {
  rowHeading: string;
  elements: Array<{ label: string; hubId?: number }>;
}

export const GameMetadataRow: FC<GameMetadataRowProps> = memo(({ rowHeading, elements }) => {
  const { auth } = usePageProps();

  if (!elements?.length) {
    return null;
  }

  const locale = auth?.user.locale?.replace('_', '-') ?? 'en-US'; // Use Intl locale code format.

  const formattedElements = elements.map((item, index) =>
    item.hubId ? (
      <HubLink key={`${rowHeading}-link-${index}`} hubId={item.hubId} label={item.label} />
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
      <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
        {rowHeading}
      </BaseTableHead>

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

interface HubLinkProps {
  hubId: number;
  label: string;
}

const HubLink: FC<HubLinkProps> = ({ hubId, label }) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicId: hubId, dynamicType: 'hub' });

  return (
    <InertiaLink href={route('hub.show', { gameSet: hubId })} {...cardTooltipProps}>
      {label}
    </InertiaLink>
  );
};
