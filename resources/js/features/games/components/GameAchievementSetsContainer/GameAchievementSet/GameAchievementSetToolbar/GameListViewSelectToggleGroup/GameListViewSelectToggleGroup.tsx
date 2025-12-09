import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar, LuTrophy } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { useCurrentListView } from '@/features/games/hooks/useCurrentListView';
import { usePreloadDeferredLeaderboards } from '@/features/games/hooks/usePreloadDeferredLeaderboards';

export const GameListViewSelectToggleGroup: FC = () => {
  const { allLeaderboards, backingGame, numLeaderboards } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const { currentListView, setCurrentListView } = useCurrentListView();

  usePreloadDeferredLeaderboards(numLeaderboards, allLeaderboards);

  return (
    <BaseToggleGroup
      type="single"
      className="flex-row-reverse gap-0 sm:flex-row"
      value={currentListView}
      onValueChange={(value) =>
        setCurrentListView(value as 'achievements' | 'leaderboards' | undefined)
      }
    >
      <BaseTooltip>
        <BaseToggleGroupItem
          size="sm"
          value="leaderboards"
          aria-label={t('Active Leaderboards')}
          variant="outline"
          className={cn(
            'game-set__toggle group w-full rounded-l-none',
            'sm:w-auto sm:rounded-l-md sm:rounded-r-none',
          )}
        >
          <BaseTooltipTrigger asChild>
            <div className="flex cursor-pointer items-center py-1">
              <LuChartBar />

              <BaseChip
                className={cn([
                  'game-set__toggle-chip',
                  currentListView === 'leaderboards' ? 'opacity-100' : null,
                ])}
              >
                {formatNumber(numLeaderboards)}
              </BaseChip>
            </div>
          </BaseTooltipTrigger>
        </BaseToggleGroupItem>

        <BaseTooltipContent>{t('Active Leaderboards')}</BaseTooltipContent>
      </BaseTooltip>

      <BaseTooltip>
        <BaseToggleGroupItem
          size="sm"
          value="achievements"
          aria-label={t('Achievements')}
          variant="outline"
          className={cn(
            'game-set__toggle w-full rounded-r-none border-r-0',
            'sm:w-auto sm:rounded-l-none sm:rounded-r-md sm:border-l-0 sm:border-r',
          )}
        >
          <BaseTooltipTrigger asChild>
            <div className="flex cursor-pointer items-center py-1">
              <span>
                <LuTrophy />
              </span>

              <BaseChip
                className={cn([
                  'game-set__toggle-chip',
                  currentListView === 'achievements' ? 'opacity-100' : null,
                ])}
              >
                {formatNumber(backingGame.achievementsPublished)}
              </BaseChip>
            </div>
          </BaseTooltipTrigger>
        </BaseToggleGroupItem>

        <BaseTooltipContent>{t('Achievements')}</BaseTooltipContent>
      </BaseTooltip>
    </BaseToggleGroup>
  );
};
