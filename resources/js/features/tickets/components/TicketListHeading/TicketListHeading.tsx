import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

export const TicketListHeading: FC = () => {
  const { t } = useTranslation();

  return (
    <div className="mb-1 flex w-full">
      <h1 className="text-h3 w-full sm:text-[2.0em]!">{t('Ticket Manager')}</h1>
    </div>
  );
};
