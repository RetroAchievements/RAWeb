import type { FC } from 'react';
import { useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseCard, BaseCardContent } from '@/common/components/+vendor/BaseCard';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCloseTicketAsNoLongerAProblemMutation } from '../../hooks/mutations/useCloseTicketAsNoLongerAProblemMutation';

export const TicketNoLongerAProblemRoot: FC = () => {
  const { canClose, closeUrl, gameTitle, ticketableTitle } =
    usePageProps<App.Platform.Data.TicketNoLongerAProblemPageProps>();
  const { t } = useTranslation();

  const [isClosed, setIsClosed] = useState(false);

  const closeTicketMutation = useCloseTicketAsNoLongerAProblemMutation();

  const handleCloseTicket = async () => {
    try {
      await closeTicketMutation.mutateAsync(closeUrl);
      setIsClosed(true);
    } catch {
      toastMessage.error(t('Something went wrong.'));
    }
  };

  return (
    <div className="container mx-auto max-w-2xl px-4 py-12">
      <BaseCard>
        <BaseCardContent className="flex flex-col items-center gap-6 pt-8 text-center">
          {canClose && !isClosed ? (
            <>
              <p className="text-balance">
                <Trans
                  i18nKey="Is {{ticketableTitle}} (<1>{{gameTitle}}</1>) working for you now?"
                  values={{ ticketableTitle }}
                  components={{ 1: <GameTitle title={gameTitle} /> }}
                />
              </p>

              <BaseButton
                onClick={handleCloseTicket}
                disabled={closeTicketMutation.isPending}
                size="sm"
                className="max-w-fit"
              >
                {t('Yes, close my ticket')}
              </BaseButton>
            </>
          ) : null}

          {isClosed ? (
            <p className="text-balance">
              {t('Thanks for letting us know. Your ticket is closed.')}
            </p>
          ) : null}

          {!canClose && !isClosed ? (
            <p className="text-balance">{t('This ticket is already closed.')}</p>
          ) : null}
        </BaseCardContent>
      </BaseCard>
    </div>
  );
};
