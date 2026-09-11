import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { RxCross2 } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';

interface TicketListResetFiltersButtonProps {
  serverDefaultColumnFilters: ColumnFiltersState;
  setColumnFilters: (updaterOrValue: Updater<ColumnFiltersState>) => void;

  onPrefetch?: () => void;
}

export const TicketListResetFiltersButton: FC<TicketListResetFiltersButtonProps> = ({
  serverDefaultColumnFilters,
  setColumnFilters,
  onPrefetch,
}) => {
  const { t } = useTranslation();

  return (
    <BaseButton
      variant="ghost"
      size="sm"
      className="px-2 text-link max-sm:h-9 lg:px-3"
      onClick={() => setColumnFilters(serverDefaultColumnFilters)}
      onMouseEnter={onPrefetch}
      data-testid="reset-all-filters"
    >
      {t('Reset')} <RxCross2 className="ml-2 size-4" />
    </BaseButton>
  );
};
