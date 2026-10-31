import { router } from '@inertiajs/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons';
import { LuChevronDown, LuCircleArrowDown } from 'react-icons/lu';

import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuGroup,
  BaseDropdownMenuItem,
  BaseDropdownMenuLabel,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { useUpdateTicketStateMutation } from '../../hooks/mutations/useUpdateTicketStateMutation';
import { useTicketCloseReasonLabels } from '../../hooks/useTicketCloseReasonLabels';
import { useTicketStateLabel } from '../../hooks/useTicketStateLabel';
import { TICKET_CLOSE_REASONS } from '../../utils/ticketCloseReasons';
import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';
import { TicketStateGlyph } from '../TicketStateGlyph';
import { TicketActionConfirmDialog } from './TicketActionConfirmDialog';

interface StateMoveItem {
  action: App.Community.Enums.TicketAction;
  Icon: IconType;
  iconClassName: string;
  label: TranslatedString;
}

export const TicketStateMenu: FC = () => {
  const { allowedTicketActions, hasNonReporterComment, hasReporterRepliedLast, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { buildTicketStateLabel } = useTicketStateLabel();
  const labelsByCloseReason = useTicketCloseReasonLabels();
  const mutation = useUpdateTicketStateMutation();

  const [pendingAction, setPendingAction] = useState<App.Community.Enums.TicketAction | null>(null);
  const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false);

  const stateLabel = buildTicketStateLabel(ticket.state, ticket.resolution);

  if (!allowedTicketActions.length) {
    return (
      <>
        <TicketStateGlyph state={ticket.state} hasTooltip={false} />
        {stateLabel}
      </>
    );
  }

  const getIsActionPermitted = (action: App.Community.Enums.TicketAction) =>
    allowedTicketActions.includes(action);

  const getIsCommentRequired = (action: App.Community.Enums.TicketAction) =>
    !hasNonReporterComment && (action === 'resolved' || action === 'closed-other');

  const reopenLabelByTicketState = {
    quarantined: t('Approve'),
    request: t('Stop waiting for the reporter'),
    resolved: t('Reopen'),
    closed: t('Reopen'),
  };

  const reopenItems: StateMoveItem[] =
    ticket.state === 'open'
      ? []
      : [
          {
            action: 'reopen',
            label: reopenLabelByTicketState[ticket.state],
            Icon: TICKET_STATE_GLYPHS.open.Icon,
            iconClassName: TICKET_STATE_GLYPHS.open.className,
          },
        ];

  // Each move shows the glyph of the state the ticket ends up in.
  const candidateStateMoveItems: StateMoveItem[] = [
    {
      action: 'resolved',
      label: t('Resolve as fixed'),
      Icon: TICKET_STATE_GLYPHS.resolved.Icon,
      iconClassName: TICKET_STATE_GLYPHS.resolved.className,
    },
    {
      action: 'request',
      label: t('Request information'),
      Icon: TICKET_STATE_GLYPHS.request.Icon,
      iconClassName: TICKET_STATE_GLYPHS.request.className,
    },
    ...reopenItems,
    {
      action: 'demoted',
      label:
        ticket.ticketableType === 'achievement' ? t('Demote achievement') : t('Demote leaderboard'),
      Icon: LuCircleArrowDown,
      iconClassName: 'text-neutral-400 light:text-neutral-600',
    },
  ];
  const stateMoveItems = candidateStateMoveItems.filter((item) =>
    getIsActionPermitted(item.action),
  );

  const closeReasons = TICKET_CLOSE_REASONS.filter(getIsActionPermitted);

  const commentRequiredHint = (
    <span className="ml-auto pl-4 text-xs">{t('Add a comment first')}</span>
  );

  // Most moves are applied instantaneously on selection, kinda like status changes in Linear.
  // confirmationCopyByAction are the ones that pop a confirm.
  const confirmationCopyByAction: Partial<
    Record<
      App.Community.Enums.TicketAction,
      { confirmLabel: TranslatedString; description?: TranslatedString; title: TranslatedString }
    >
  > = {
    demoted: {
      title:
        ticket.ticketableType === 'achievement'
          ? t('Demote this achievement to unpublished?')
          : t('Demote this leaderboard to unpromoted?'),
      description: t('This also closes the ticket.'),
      confirmLabel: t('Demote'),
    },
    'unable-to-debug': {
      title: t('Close as unable to debug?'),
      description: t(
        'If the player gave steps to reproduce the problem, try to reproduce it before you close the ticket.',
      ),
      confirmLabel: t('Close ticket'),
    },
    'closed-mistaken': {
      title: t('Close as a mistaken report?'),
      confirmLabel: t('Close ticket'),
    },
    ...(hasReporterRepliedLast
      ? {
          request: {
            title: t('Request information from the reporter?'),
            description: t(
              'The reporter made the latest comment. Do you want to hand the ticket back to the reporter without a new comment?',
            ),
            confirmLabel: t('Request information'),
          },
        }
      : {}),
  };

  const runAction = (action: App.Community.Enums.TicketAction) => {
    toastMessage.promise(mutation.mutateAsync({ ticketId: ticket.id, action }), {
      loading: t('Updating...'),
      success: () => {
        router.reload();

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  const selectAction = (action: App.Community.Enums.TicketAction) => {
    if (confirmationCopyByAction[action]) {
      setPendingAction(action);
      setIsConfirmDialogOpen(true);

      return;
    }

    runAction(action);
  };

  const pendingConfirmationCopy = pendingAction ? confirmationCopyByAction[pendingAction] : null;
  const ClosedIcon = TICKET_STATE_GLYPHS.closed.Icon;

  return (
    <>
      <BaseDropdownMenu>
        <BaseDropdownMenuTrigger
          aria-label={t('Change status (currently {{status}})', { status: stateLabel })}
          className={cn(
            'flex items-center gap-1.5 rounded-md border px-2 py-1',
            'border-neutral-700 bg-neutral-800/50',
            'hover:border-neutral-600 hover:bg-neutral-800',
            'data-[state=open]:border-neutral-600 data-[state=open]:bg-neutral-800',
            'light:border-neutral-300 light:bg-white',
            'light:hover:border-neutral-400 light:hover:bg-neutral-100',
            'light:data-[state=open]:border-neutral-400 light:data-[state=open]:bg-neutral-100',
            'outline-hidden focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-neutral-300',
            'light:focus-visible:ring-neutral-950',
          )}
        >
          <TicketStateGlyph state={ticket.state} hasTooltip={false} />
          {stateLabel}
          <LuChevronDown
            aria-hidden={true}
            className="size-3.5 text-neutral-400 light:text-neutral-600"
          />
        </BaseDropdownMenuTrigger>

        <BaseDropdownMenuContent align="start" className="min-w-56">
          {stateMoveItems.map((item) => (
            <BaseDropdownMenuItem
              key={item.action}
              className="gap-2"
              disabled={getIsCommentRequired(item.action)}
              onSelect={() => selectAction(item.action)}
            >
              <item.Icon aria-hidden={true} className={cn('size-4', item.iconClassName)} />
              {item.label}
              {getIsCommentRequired(item.action) ? commentRequiredHint : null}
            </BaseDropdownMenuItem>
          ))}

          {stateMoveItems.length && closeReasons.length ? <BaseDropdownMenuSeparator /> : null}

          {closeReasons.length ? (
            <BaseDropdownMenuGroup>
              <BaseDropdownMenuLabel className="flex items-center gap-2 font-normal text-neutral-400 light:text-neutral-600">
                <ClosedIcon
                  aria-hidden={true}
                  className={cn('size-4', TICKET_STATE_GLYPHS.closed.className)}
                />
                {t('Close as')}
              </BaseDropdownMenuLabel>

              {closeReasons.map((closeReason) => (
                <BaseDropdownMenuItem
                  key={closeReason}
                  inset={true}
                  disabled={getIsCommentRequired(closeReason)}
                  onSelect={() => selectAction(closeReason)}
                >
                  {labelsByCloseReason[closeReason]}
                  {getIsCommentRequired(closeReason) ? commentRequiredHint : null}
                </BaseDropdownMenuItem>
              ))}
            </BaseDropdownMenuGroup>
          ) : null}
        </BaseDropdownMenuContent>
      </BaseDropdownMenu>

      {pendingAction && pendingConfirmationCopy ? (
        <TicketActionConfirmDialog
          {...pendingConfirmationCopy}
          isOpen={isConfirmDialogOpen}
          onOpenChange={setIsConfirmDialogOpen}
          onConfirm={() => runAction(pendingAction)}
        />
      ) : null}
    </>
  );
};
