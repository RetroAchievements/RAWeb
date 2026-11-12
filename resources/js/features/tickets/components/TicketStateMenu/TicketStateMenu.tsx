import { router } from '@inertiajs/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuItem,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useUpdateTicketStateMutation } from '../../hooks/mutations/useUpdateTicketStateMutation';
import { useFormatTicketState } from '../../hooks/useFormatTicketState';
import { useTicketStateLabels } from '../../hooks/useTicketStateLabels';
import { TICKET_CLOSE_REASON_LABELS } from '../../utils/ticketCloseReasonLabels';
import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';
import { TicketActionConfirmDialog } from '../TicketActionConfirmDialog';
import { TicketCloseDialog } from './TicketCloseDialog';

export const TicketStateMenu: FC = () => {
  const { allowedTicketActions, auth, can, comments, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { formatTicketState } = useFormatTicketState();
  const ticketStateLabels = useTicketStateLabels();

  const mutation = useUpdateTicketStateMutation();

  const [isMistakenReportDialogOpen, setIsMistakenReportDialogOpen] = useState(false);
  const [isCloseDialogOpen, setIsCloseDialogOpen] = useState(false);

  const stateLabel = formatTicketState(ticket.state, ticket.resolution);

  const getIsActionPermitted = (action: App.Community.Enums.TicketAction) =>
    allowedTicketActions.includes(action);

  const canRequestInfo = getIsActionPermitted('request') && !!can.createTicketComments;
  const isViewerLatestCommenter =
    !!auth?.user && comments.at(-1)?.user.displayName === auth.user.displayName;

  let requestInfoHint: string | null = null;
  if (!isViewerLatestCommenter) {
    requestInfoHint = t('Add a comment first');
  } else if (can.claimTicket) {
    requestInfoHint = t('Reassigns this ticket to you');
  }

  const reopenLabelByTicketState = {
    quarantined: t('Approve'),
    request: t('Stop waiting for the reporter'),
    resolved: t('Reopen'),
    closed: t('Reopen'),
  };
  const reopenMenuItemLabel =
    ticket.state === 'open' || !getIsActionPermitted('reopen')
      ? null
      : reopenLabelByTicketState[ticket.state];

  const hasStaffCloseReasons = allowedTicketActions.some(
    (action) => action !== 'closed-mistaken' && action in TICKET_CLOSE_REASON_LABELS,
  );
  const canCloseAsMistakenReport = !hasStaffCloseReasons && getIsActionPermitted('closed-mistaken');

  const runAction = (action: App.Community.Enums.TicketAction) => {
    toastMessage.promise(mutation.mutateAsync({ ticketId: ticket.id, payload: { action } }), {
      loading: t('Updating...'),
      success: () => {
        router.reload();

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  const CurrentTicketStateGlyph = TICKET_STATE_GLYPHS[ticket.state].Icon;

  // Each move shows the glyph of the state the ticket will end up in.
  const RequestInfoGlyph = TICKET_STATE_GLYPHS.request.Icon;
  const ReopenGlyph = TICKET_STATE_GLYPHS.open.Icon;
  const ClosedIcon = TICKET_STATE_GLYPHS.closed.Icon;
  const closedMenuGlyph = (
    <ClosedIcon aria-hidden={true} className={cn('size-4', TICKET_STATE_GLYPHS.closed.className)} />
  );

  return (
    <>
      <BaseDropdownMenu>
        <BaseDropdownMenuTrigger asChild>
          <BaseButton
            aria-label={t('Change status (currently {{status}})', { status: stateLabel })}
            className={cn(
              'gap-2 px-3 lg:transition-none lg:active:translate-y-0 lg:active:scale-100',
              'text-neutral-200 hover:text-neutral-50 data-[state=open]:bg-embed-highlight',
              'light:border-neutral-300 light:text-neutral-800 light:hover:text-neutral-950',
            )}
          >
            <CurrentTicketStateGlyph
              aria-hidden={true}
              className={cn('size-4', TICKET_STATE_GLYPHS[ticket.state].className)}
            />

            {ticketStateLabels[ticket.state]}

            <LuChevronDown
              aria-hidden={true}
              className="size-3.5 text-neutral-400 light:text-neutral-600"
            />
          </BaseButton>
        </BaseDropdownMenuTrigger>

        <BaseDropdownMenuContent align="end" className="min-w-56">
          {canRequestInfo ? (
            <BaseDropdownMenuItem
              disabled={!isViewerLatestCommenter}
              className="items-start gap-2"
              onSelect={() => runAction('request')}
            >
              <span className="flex h-lh items-center">
                <RequestInfoGlyph
                  aria-hidden={true}
                  className={cn('size-4', TICKET_STATE_GLYPHS.request.className)}
                />
              </span>

              <span className="flex flex-col">
                <span>{t('Request information')}</span>

                {requestInfoHint ? (
                  <span className="text-xs text-neutral-400 light:text-neutral-600">
                    {requestInfoHint}
                  </span>
                ) : null}
              </span>
            </BaseDropdownMenuItem>
          ) : null}

          {reopenMenuItemLabel ? (
            <BaseDropdownMenuItem className="gap-2" onSelect={() => runAction('reopen')}>
              <ReopenGlyph
                aria-hidden={true}
                className={cn('size-4', TICKET_STATE_GLYPHS.open.className)}
              />
              {reopenMenuItemLabel}
            </BaseDropdownMenuItem>
          ) : null}

          {(canRequestInfo || reopenMenuItemLabel) &&
          (hasStaffCloseReasons || canCloseAsMistakenReport) ? (
            <BaseDropdownMenuSeparator />
          ) : null}

          {hasStaffCloseReasons ? (
            <BaseDropdownMenuItem className="gap-2" onSelect={() => setIsCloseDialogOpen(true)}>
              {closedMenuGlyph}
              {t('Close…')}
            </BaseDropdownMenuItem>
          ) : null}

          {canCloseAsMistakenReport ? (
            <BaseDropdownMenuItem
              className="gap-2"
              onSelect={() => setIsMistakenReportDialogOpen(true)}
            >
              {closedMenuGlyph}
              {t('Mistaken report')}
            </BaseDropdownMenuItem>
          ) : null}
        </BaseDropdownMenuContent>
      </BaseDropdownMenu>

      {canCloseAsMistakenReport ? (
        <TicketActionConfirmDialog
          title={t('Close as a mistaken report?')}
          confirmLabel={t('Close ticket')}
          isOpen={isMistakenReportDialogOpen}
          onOpenChange={setIsMistakenReportDialogOpen}
          onConfirm={() => runAction('closed-mistaken')}
        />
      ) : null}

      {hasStaffCloseReasons ? (
        <TicketCloseDialog isOpen={isCloseDialogOpen} onOpenChange={setIsCloseDialogOpen} />
      ) : null}
    </>
  );
};
