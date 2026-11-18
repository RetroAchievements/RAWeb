import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { useClaimTicketMutation } from '../../hooks/mutations/useClaimTicketMutation';
import { useReleaseTicketClaimMutation } from '../../hooks/mutations/useReleaseTicketClaimMutation';
import { useRunTicketClaimRequest } from '../../hooks/useRunTicketClaimRequest';
import { getTicketResponsibleDeveloper } from '../../utils/getTicketResponsibleDeveloper';
import { TicketActionConfirmDialog } from '../TicketActionConfirmDialog';

interface ClaimAction {
  mutation: ReturnType<typeof useClaimTicketMutation>;
  t_label: TranslatedString;
  t_title: TranslatedString;

  t_description?: TranslatedString;
}

export const TicketClaimActions: FC = () => {
  const ticketPageProps = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { can, ticket } = ticketPageProps;
  const { t } = useTranslation();

  const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false);

  const claimTicketMutation = useClaimTicketMutation();
  const releaseClaimMutation = useReleaseTicketClaimMutation();
  const runTicketClaimRequest = useRunTicketClaimRequest();

  let claimAction: ClaimAction | null = null;
  if (can.claimTicket) {
    claimAction = {
      mutation: claimTicketMutation,
      t_label: t('Claim ticket'),
      t_title: t('Claim this ticket?'),
      t_description: t(
        "You become responsible for this ticket. The reporter's replies are sent as notifications to you. Your claim will automatically end after 14 days if there's no activity from you on the ticket.",
      ),
    };
  } else if (can.releaseTicketClaim) {
    const responsibleDeveloper = getTicketResponsibleDeveloper(ticketPageProps);

    claimAction = {
      mutation: releaseClaimMutation,
      t_label: t('Release claim'),
      t_title: t('Release this claim?'),
      t_description: responsibleDeveloper
        ? t('The ticket will be reassigned back to {{name}}.', {
            name: responsibleDeveloper.displayName,
          })
        : undefined,
    };
  }

  if (!claimAction) {
    return null;
  }

  const { mutation } = claimAction;

  return (
    <>
      <BaseButton
        size="xs"
        onClick={() => setIsConfirmDialogOpen(true)}
        disabled={mutation.isPending || mutation.isSuccess}
      >
        {claimAction.t_label}
      </BaseButton>

      <TicketActionConfirmDialog
        isOpen={isConfirmDialogOpen}
        onOpenChange={setIsConfirmDialogOpen}
        onConfirm={() => runTicketClaimRequest(mutation.mutateAsync({ ticketId: ticket.id }))}
        title={claimAction.t_title}
        description={claimAction.t_description}
        confirmLabel={claimAction.t_label}
      />
    </>
  );
};
