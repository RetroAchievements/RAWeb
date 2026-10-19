import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { TicketAwardReporterUnlockDialog } from '../TicketAwardReporterUnlockDialog';
import { TicketUserValue } from '../TicketUserValue';

interface TicketReporterNoUnlockFactProps {
  reporter: App.Data.User;
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketReporterNoUnlockFact: FC<TicketReporterNoUnlockFactProps> = ({
  reporter,
  ticket,
}) => {
  const { can } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const isHardcoreReport = !!ticket.hardcore;

  return (
    <>
      <Trans
        i18nKey="<1>{{user}}</1> has not unlocked this achievement."
        components={{ 1: <TicketUserValue user={reporter} /> }}
      />

      {can.manuallyAwardAchievements ? (
        <span className="mt-2 flex flex-wrap items-center gap-2">
          {isHardcoreReport ? (
            <>
              <TicketAwardReporterUnlockDialog
                reporter={reporter}
                ticket={ticket}
                unlockMode="hardcore"
                trigger={<BaseButton size="sm">{t('Award hardcore')}</BaseButton>}
              />

              <TicketAwardReporterUnlockDialog
                reporter={reporter}
                ticket={ticket}
                unlockMode="casual"
                trigger={
                  <BaseButton variant="link" size="sm">
                    {t('Award casual instead')}
                  </BaseButton>
                }
              />
            </>
          ) : (
            <TicketAwardReporterUnlockDialog
              reporter={reporter}
              ticket={ticket}
              unlockMode="casual"
              trigger={<BaseButton size="sm">{t('Award casual')}</BaseButton>}
            />
          )}
        </span>
      ) : null}
    </>
  );
};
