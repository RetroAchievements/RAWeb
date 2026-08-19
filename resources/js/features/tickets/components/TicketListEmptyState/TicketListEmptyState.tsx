import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

export const TicketListEmptyState: FC = () => {
  const { t } = useTranslation();

  return (
    <p className="py-[0.4em] pb-[1.2em] text-neutral-400 light:text-neutral-600">
      {t('No tickets match these filters.')}
    </p>
  );
};
