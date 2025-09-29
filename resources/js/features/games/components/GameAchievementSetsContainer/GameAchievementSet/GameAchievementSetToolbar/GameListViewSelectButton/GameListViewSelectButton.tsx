import { useAtom, useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar, LuTrophy } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseChip } from '@/common/components/+vendor/BaseChip';
import {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
} from '@/features/games/state/games.atoms';

export const GameListViewSelectButton: FC = () => {
  const { backingGame, numLeaderboards } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const [currentListView, setCurrentListView] = useAtom(currentListViewAtom);
  const setCurrentSort = useSetAtom(currentPlayableListSortAtom);

  const Icon = currentListView === 'achievements' ? LuTrophy : LuChartBar;

  const handleViewChange = (view: 'achievements' | 'leaderboards') => {
    setCurrentListView(view);

    // Set the appropriate default sort when switching views.
    if (view === 'leaderboards') {
      setCurrentSort('displayOrder');
    } else {
      setCurrentSort('normal');
    }

    const url = new URL(window.location.href);

    if (view === 'leaderboards') {
      url.searchParams.set('view', 'leaderboards');
    } else {
      url.searchParams.delete('view');
    }

    window.history.replaceState({}, '', url.toString());
  };

  return (
    <BaseDropdownMenu modal={false}>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton size="sm" aria-label={t('Display mode')}>
          <Icon className="size-4" />
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start">
        <BaseDropdownMenuCheckboxItem
          checked={currentListView === 'achievements'}
          onClick={() => handleViewChange('achievements')}
          className="justify-between gap-2.5"
        >
          <span className="flex items-center gap-1.5">
            <LuTrophy />
            {t('Achievements')}
          </span>

          <BaseChip className="bg-neutral-800">
            {formatNumber(backingGame.achievementsPublished)}
          </BaseChip>
        </BaseDropdownMenuCheckboxItem>

        <BaseDropdownMenuCheckboxItem
          checked={currentListView === 'leaderboards'}
          onClick={() => handleViewChange('leaderboards')}
          className="justify-between gap-2.5"
        >
          <span className="flex items-center gap-1.5">
            <LuChartBar />
            {t('Leaderboards')}
          </span>

          <BaseChip className="bg-neutral-800">{formatNumber(numLeaderboards)}</BaseChip>
        </BaseDropdownMenuCheckboxItem>
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
