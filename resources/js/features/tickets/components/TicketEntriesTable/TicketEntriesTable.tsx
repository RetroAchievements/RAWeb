import { useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, type ReactNode, type Ref, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';
import { cn } from '@/common/utils/cn';

import { TicketDateTooltip } from '../TicketDateTooltip';
import { TicketStateGlyph } from '../TicketStateGlyph';
import { useTableHeights } from './useTableHeights';

const ROW_COLLAPSE_MOTION = { duration: 0.25, ease: [0.23, 1, 0.32, 1] as const };
const EXPANDED_HEIGHT_CEILING_PX = 480; // roughly fifteen rows

interface TicketEntriesTableProps {
  entries: App.Platform.Data.TicketRelatedEntry[];
  getLabel: (entry: App.Platform.Data.TicketRelatedEntry) => string;
  labelHeading: string;

  collapsedRowCount?: number;
  footer?: ReactNode;
  isExpanded?: boolean;
  timingFrom?: string;
}

export const TicketEntriesTable: FC<TicketEntriesTableProps> = ({
  collapsedRowCount,
  footer,
  isExpanded = false,
  ...rest
}) => {
  return (
    <div className="flex flex-col gap-2 rounded-lg bg-embed p-2 light:border light:border-neutral-200 light:bg-white">
      {collapsedRowCount === undefined ? (
        <TicketRowsTable {...rest} />
      ) : (
        <CollapsibleTicketTable
          {...rest}
          collapsedRowCount={collapsedRowCount}
          isExpanded={isExpanded}
        />
      )}

      {footer ? <div className="flex justify-center p-1">{footer}</div> : null}
    </div>
  );
};

type TableContentProps = Pick<
  TicketEntriesTableProps,
  'entries' | 'getLabel' | 'labelHeading' | 'timingFrom'
>;

interface CollapsibleTicketTableProps extends TableContentProps {
  collapsedRowCount: number;
  isExpanded: boolean;
}

const CollapsibleTicketTable: FC<CollapsibleTicketTableProps> = ({
  collapsedRowCount,
  entries,
  isExpanded,
  ...rest
}) => {
  const prefersReducedMotion = useReducedMotion();

  const { measuredHeights, measuredTableRef } = useTableHeights(collapsedRowCount);
  const cappedExpandedHeight =
    measuredHeights && Math.min(measuredHeights.expanded, EXPANDED_HEIGHT_CEILING_PX);

  const [rendersEveryRow, setRendersEveryRow] = useState(isExpanded);
  if (isExpanded && !rendersEveryRow) {
    setRendersEveryRow(true);
  }

  const showsEveryEntry = isExpanded || (rendersEveryRow && measuredHeights !== null);

  return (
    <m.div
      initial={false}
      animate={{
        height: (isExpanded ? cappedExpandedHeight : measuredHeights?.collapsed) ?? 'auto',
      }}
      transition={prefersReducedMotion ? { duration: 0 } : ROW_COLLAPSE_MOTION}
      onAnimationComplete={() => {
        if (!isExpanded) {
          setRendersEveryRow(false);
        }
      }}
      className={cn(
        'rounded-lg',
        isExpanded && (measuredHeights?.expanded ?? 0) > EXPANDED_HEIGHT_CEILING_PX
          ? 'overflow-y-auto'
          : 'overflow-hidden',
      )}
    >
      <TicketRowsTable
        {...rest}
        ref={measuredTableRef}
        entries={showsEveryEntry ? entries : entries.slice(0, collapsedRowCount)}
        isHeaderPinned={isExpanded}
      />
    </m.div>
  );
};

interface TicketRowsTableProps extends TableContentProps {
  isHeaderPinned?: boolean;
  ref?: Ref<HTMLTableElement>;
}

const TicketRowsTable: FC<TicketRowsTableProps> = ({
  entries,
  getLabel,
  labelHeading,
  ref,
  timingFrom,
  isHeaderPinned = false,
}) => {
  const { t } = useTranslation();

  return (
    <BaseTable
      ref={ref}
      className={cn(
        'table-highlight rounded-lg outline-1 outline-neutral-800 light:outline-white',
        !isHeaderPinned ? 'overflow-hidden' : null,
      )}
    >
      <BaseTableHeader
        className={cn(
          'border-neutral-800',
          isHeaderPinned ? 'sticky top-0 z-20 bg-embed light:bg-white' : null,
        )}
      >
        <BaseTableRow className="do-not-highlight text-menu-link">
          <BaseTableCell>{t('Ticket')}</BaseTableCell>
          <BaseTableCell>{labelHeading}</BaseTableCell>
          <BaseTableCell className="text-right">
            {timingFrom ? t('Opened') : t('Created')}
          </BaseTableCell>
        </BaseTableRow>
      </BaseTableHeader>

      <BaseTableBody>
        {entries.map((entry) => (
          <TicketEntryRow
            key={entry.id}
            entry={entry}
            entryLabel={getLabel(entry)}
            timingFrom={timingFrom}
          />
        ))}
      </BaseTableBody>
    </BaseTable>
  );
};

interface TicketEntryRowProps {
  entry: App.Platform.Data.TicketRelatedEntry;
  entryLabel: string;

  timingFrom?: string;
}

const TicketEntryRow: FC<TicketEntryRowProps> = ({ entry, entryLabel, timingFrom }) => {
  return (
    <BaseTableRow className="last:rounded-b-lg [&>td]:py-1.5 [&>td]:align-top">
      <BaseTableCell className="whitespace-nowrap">
        <span className="flex items-center gap-2">
          <TicketStateGlyph state={entry.state} />

          <InertiaLink
            href={route('ticket.show', { ticket: entry.id })}
            prefetch="desktop-hover-only"
          >
            {`#${entry.id}`}
          </InertiaLink>
        </span>
      </BaseTableCell>

      <BaseTableCell className="w-full max-w-0 wrap-break-word">{entryLabel}</BaseTableCell>

      <BaseTableCell className="text-right whitespace-nowrap">
        <EntryAgeCell createdAt={entry.createdAt} timingFrom={timingFrom} />
      </BaseTableCell>
    </BaseTableRow>
  );
};

interface EntryAgeCellProps {
  createdAt: string;

  timingFrom?: string;
}

const EntryAgeCell: FC<EntryAgeCellProps> = ({ createdAt, timingFrom }) => {
  const { t } = useTranslation();
  const { formatCoarseDuration } = useFormatCoarseDuration();

  if (!timingFrom) {
    return <DiffTimestamp at={createdAt} style="narrow" className="smalldate" />;
  }

  const duration = formatCoarseDuration(timingFrom, createdAt, 'narrow');

  return (
    <TicketDateTooltip date={createdAt} className="smalldate">
      {createdAt < timingFrom
        ? t('{{duration}} earlier', { duration })
        : t('{{duration}} later', { duration })}
    </TicketDateTooltip>
  );
};
