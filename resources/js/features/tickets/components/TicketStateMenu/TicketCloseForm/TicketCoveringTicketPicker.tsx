import { router } from '@inertiajs/react';
import { type FC, useId, useState } from 'react';
import { useFormContext, useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { HiOutlineCheck } from 'react-icons/hi';
import { LuChevronsUpDown } from 'react-icons/lu';
import { route } from 'ziggy-js';

import {
  BaseCommand,
  BaseCommandEmpty,
  BaseCommandGroup,
  BaseCommandInput,
  BaseCommandItem,
  BaseCommandList,
} from '@/common/components/+vendor/BaseCommand';
import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { useTicketResolutionLabels } from '../../../hooks/useTicketResolutionLabels';
import { useTicketStateLabels } from '../../../hooks/useTicketStateLabels';
import { TICKET_STATE_GLYPHS } from '../../../utils/ticketStateGlyphs';
import type { FormValues } from './useTicketCloseForm';

interface CoveringTicketGroup {
  entries: App.Platform.Data.TicketRelatedEntry[];
  t_heading: TranslatedString;
}

export const TicketCoveringTicketPicker: FC = () => {
  const { otherReporterGameTickets, relatedTickets, reporterGameTickets, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { control, setValue } = useFormContext<FormValues>();
  const coveringTicketId = useWatch({ control, name: 'coveringTicketId' });

  const [isPickerOpen, setIsPickerOpen] = useState(false);
  const [isFetchingOtherReporterTickets, setIsFetchingOtherReporterTickets] = useState(false);

  const promptElementId = useId();
  const selectionElementId = useId();

  const ticketGroups: CoveringTicketGroup[] = [
    { t_heading: t('From this reporter on this game'), entries: reporterGameTickets },
    {
      t_heading:
        ticket.ticketableType === 'achievement'
          ? t('On this achievement')
          : t('On this leaderboard'),
      entries: relatedTickets,
    },
    { t_heading: t('From other reporters on this game'), entries: otherReporterGameTickets ?? [] },
  ];
  const groupsWithEntries = ticketGroups.filter((group) => group.entries.length > 0);

  const chosenEntry =
    ticketGroups.flatMap((group) => group.entries).find((entry) => entry.id === coveringTicketId) ??
    null;

  const handlePickerOpenChange = (isOpen: boolean) => {
    setIsPickerOpen(isOpen);

    // Defer fetching other reporters' tickets until the list is opened for the first time.
    if (isOpen && otherReporterGameTickets === undefined && !isFetchingOtherReporterTickets) {
      router.reload({
        only: ['otherReporterGameTickets'],
        onStart: () => setIsFetchingOtherReporterTickets(true),
        onFinish: () => setIsFetchingOtherReporterTickets(false),
      });
    }
  };

  const handleTicketPick = (ticketId: number) => {
    setValue('coveringTicketId', ticketId, { shouldValidate: true });
    setIsPickerOpen(false);
  };

  return (
    <div className="flex flex-col gap-3">
      <p id={promptElementId} className="font-medium text-menu-link">
        {t('Which ticket?')}
      </p>

      <BasePopover modal={true} open={isPickerOpen} onOpenChange={handlePickerOpenChange}>
        <BasePopoverTrigger asChild>
          <button
            type="button"
            aria-labelledby={`${promptElementId} ${selectionElementId}`}
            className={cn(
              'flex h-10 w-full items-center gap-2 rounded-md border px-3 text-left text-sm',
              'border-neutral-700 bg-neutral-950 hover:border-neutral-600',
              'light:border-neutral-300 light:bg-white light:hover:border-neutral-400',
              'outline-hidden focus-visible:ring-2 focus-visible:ring-neutral-300 light:focus-visible:ring-neutral-950',
            )}
          >
            {chosenEntry ? (
              <span id={selectionElementId} className="flex min-w-0 items-center gap-2">
                <span className="text-neutral-400 tabular-nums light:text-neutral-600">
                  {'#'}
                  {chosenEntry.id}
                </span>

                <span className="truncate text-neutral-200 light:text-neutral-900">
                  {chosenEntry.ticketableTitle}
                </span>
              </span>
            ) : (
              <span id={selectionElementId} className="text-neutral-400 light:text-neutral-600">
                {t('Choose a ticket…')}
              </span>
            )}

            <LuChevronsUpDown
              aria-hidden={true}
              className="ml-auto size-4 shrink-0 text-neutral-400 light:text-neutral-600"
            />
          </button>
        </BasePopoverTrigger>

        <BasePopoverContent
          align="start"
          className="w-(--radix-popover-trigger-width) max-w-(--radix-popover-content-available-width) p-0 sm:min-w-96"
        >
          <BaseCommand>
            <BaseCommandInput placeholder={t('Search by title, number, or reporter')} />

            <BaseCommandList>
              {isFetchingOtherReporterTickets ? null : (
                <BaseCommandEmpty>{t('No results found.')}</BaseCommandEmpty>
              )}

              {groupsWithEntries.map((group) => (
                <BaseCommandGroup key={group.t_heading} heading={group.t_heading}>
                  {group.entries.map((entry) => (
                    <BaseCommandItem
                      key={entry.id}
                      value={`#${entry.id} ${entry.ticketableTitle} ${entry.reporter?.displayName ?? ''}`}
                      onSelect={() => handleTicketPick(entry.id)}
                    >
                      <CoveringTicketOption
                        entry={entry}
                        isSelected={entry.id === coveringTicketId}
                      />
                    </BaseCommandItem>
                  ))}
                </BaseCommandGroup>
              ))}

              {isFetchingOtherReporterTickets ? (
                <p
                  role="status"
                  className="px-3 py-2 text-xs text-neutral-400 light:text-neutral-600"
                >
                  {t("Loading other reporters' tickets…")}
                </p>
              ) : null}
            </BaseCommandList>
          </BaseCommand>
        </BasePopoverContent>
      </BasePopover>

      {chosenEntry ? (
        <InertiaLink
          href={route('ticket.show', { ticket: chosenEntry.id })}
          target="_blank"
          rel="noreferrer"
          className="self-start text-xs"
        >
          {t('View this ticket')}
        </InertiaLink>
      ) : null}
    </div>
  );
};

interface CoveringTicketOptionProps {
  entry: App.Platform.Data.TicketRelatedEntry;
  isSelected: boolean;
}

const CoveringTicketOption: FC<CoveringTicketOptionProps> = ({ entry, isSelected }) => {
  const resolutionLabels = useTicketResolutionLabels();
  const stateLabels = useTicketStateLabels();

  const StateGlyph = TICKET_STATE_GLYPHS[entry.state].Icon;

  const statusText = entry.resolution
    ? resolutionLabels[entry.resolution]
    : stateLabels[entry.state];

  return (
    <span className="flex w-full min-w-0 items-center gap-2">
      <StateGlyph
        aria-hidden={true}
        className={cn('size-4 shrink-0', TICKET_STATE_GLYPHS[entry.state].className)}
      />
      <span className="sr-only">{statusText}</span>

      <span className="shrink-0 text-neutral-400 tabular-nums light:text-neutral-600">
        {'#'}
        {entry.id}
      </span>

      <span className="min-w-0 truncate text-neutral-200 light:text-neutral-900">
        {entry.ticketableTitle}
      </span>

      <span className="ml-auto max-w-[45%] shrink-0 truncate pl-2 text-xs text-neutral-400 light:text-neutral-600">
        <DiffTimestamp at={entry.createdAt} style="narrow" enableTooltip={false} />
        {entry.reporter ? ` · ${entry.reporter.displayName}` : null}
      </span>

      <HiOutlineCheck
        aria-hidden={true}
        className={cn('size-4 shrink-0', isSelected ? 'opacity-100' : 'opacity-0')}
      />
    </span>
  );
};
