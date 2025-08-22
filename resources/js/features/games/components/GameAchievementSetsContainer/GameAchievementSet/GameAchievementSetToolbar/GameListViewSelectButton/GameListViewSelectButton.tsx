import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar, LuTrophy } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { currentListViewAtom } from '@/features/games/state/games.atoms';

export const GameListViewSelectButton: FC = () => {
  const { t } = useTranslation();

  const [currentListView, setCurrentListView] = useAtom(currentListViewAtom);

  const Icon = currentListView === 'achievements' ? LuTrophy : LuChartBar;

  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton size="sm" aria-label={t('Display mode')}>
          <Icon className="size-4" />
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start">
        <BaseDropdownMenuCheckboxItem
          checked={currentListView === 'achievements'}
          onClick={() => setCurrentListView('achievements')}
          className="gap-1.5"
        >
          <LuTrophy />
          {t('Achievements')}
        </BaseDropdownMenuCheckboxItem>

        <BaseDropdownMenuCheckboxItem
          checked={currentListView === 'leaderboards'}
          onClick={() => setCurrentListView('leaderboards')}
          className="gap-1.5"
        >
          <LuChartBar />
          {t('Leaderboards')}
        </BaseDropdownMenuCheckboxItem>
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
