import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';

import { TicketViewSwitcher } from '../TicketViewSwitcher';

interface TicketPageLayoutProps {
  children: ReactNode;
  currentView: 'mine' | 'all';
}

export const TicketPageLayout: FC<TicketPageLayoutProps> = ({ children, currentView }) => {
  const { t } = useTranslation();

  return (
    <AppLayout withSidebar={false}>
      <AppLayout.Main>
        <div className="flex flex-col gap-4">
          <header className="flex flex-col gap-1">
            <h1 className="text-h3 w-full text-text sm:text-[2.0em]!">{t('Tickets')}</h1>
            <TicketViewSwitcher currentView={currentView} />
          </header>

          {children}
        </div>
      </AppLayout.Main>
    </AppLayout>
  );
};
