import { useQueryClient } from '@tanstack/react-query';
import type { ColumnFiltersState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDices } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';

import { useRandomGameQuery } from './useRandomGameQuery';

interface RandomGameButtonProps {
  columnFilters: ColumnFiltersState;
  variant: 'mobile-drawer' | 'toolbar';

  apiRouteName?: RouteName;
}

export const RandomGameButton: FC<RandomGameButtonProps> = ({
  columnFilters,
  variant,
  apiRouteName = 'api.game.random',
}) => {
  const { t } = useTranslation();

  const queryClient = useQueryClient();

  const { data } = useRandomGameQuery({ apiRouteName, columnFilters });

  const handleClick = () => {
    if (variant === 'mobile-drawer' && data?.gameId) {
      window.location.assign(route('game.show', data.gameId));
    }

    if (variant === 'toolbar') {
      queryClient.invalidateQueries({
        queryKey: ['random-game', { columnFilters, apiRouteName }],
        exact: true,
      });
    }
  };

  if (variant === 'mobile-drawer') {
    return (
      <BaseButton variant="secondary" className="gap-1.5" onClick={handleClick}>
        <LuDices className="size-4" />
        {t('Surprise me')}
      </BaseButton>
    );
  }

  return (
    <a
      href={route('game.show', data?.gameId ?? 1)}
      target="_blank"
      className={baseButtonVariants({ size: 'sm', className: 'group gap-1' })}
      onClick={handleClick}
    >
      <LuDices className="size-4 transition-transform duration-100 group-hover:rotate-12" />
      <span className="hidden sm:inline md:hidden xl:inline">{t('Surprise me')}</span>
    </a>
  );
};
