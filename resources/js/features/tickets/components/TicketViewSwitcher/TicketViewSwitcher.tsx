import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { baseChipVariants } from '@/common/components/+vendor/BaseChip';
import { InertiaLink } from '@/common/components/InertiaLink';
import { cn } from '@/common/utils/cn';

interface TicketViewSwitcherProps {
  currentView: 'mine' | 'all';
}

export const TicketViewSwitcher: FC<TicketViewSwitcherProps> = ({ currentView }) => {
  const { t } = useTranslation();

  const pillButtons = [
    { view: 'mine', href: route('tickets.mine'), label: t('For you') },
    { view: 'all', href: route('tickets.index'), label: t('All tickets') },
  ];

  return (
    <nav aria-label={t('Tickets')} className="flex gap-2">
      {pillButtons.map(({ view, href, label }) => (
        <InertiaLink
          key={view}
          href={href}
          prefetch={currentView === view ? 'never' : 'desktop-hover-only'}
          aria-current={currentView === view ? 'page' : undefined}
          className={cn(
            baseChipVariants(),
            'border py-1 transition-colors duration-150 motion-reduce:transition-none',
            currentView === view
              ? 'border-neutral-200 bg-neutral-800! text-neutral-50 hover:text-neutral-50'
              : 'border-neutral-700 text-neutral-300 hover:bg-neutral-800 hover:text-neutral-300 light:bg-neutral-100 light:text-neutral-700',
          )}
        >
          {label}
        </InertiaLink>
      ))}
    </nav>
  );
};
