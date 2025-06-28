import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { IoAlert } from 'react-icons/io5';
import { LuEyeOff } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { BaseToggle } from '@/common/components/+vendor/BaseToggle';
import { AchievementSortButton } from '@/common/components/AchievementSortButton';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { usePersistedGameIdsCookie } from '@/features/games/hooks/usePersistedGameIdsCookie';
import {
  currentAchievementSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';

// TODO light mode
// TODO mobile

interface GameAchievementSetToolbarProps {
  lockedAchievementsCount: number;
  missableAchievementsCount: number;
}

export const GameAchievementSetToolbar: FC<GameAchievementSetToolbarProps> = ({
  lockedAchievementsCount,
  missableAchievementsCount,
}) => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();
  const { formatNumber } = useFormatNumber();

  const lockedOnlyCookie = usePersistedGameIdsCookie('hide_unlocked_achievements_games', game.id);
  const missableOnlyCookie = usePersistedGameIdsCookie(
    'hide_nonmissable_achievements_games',
    game.id,
  );

  const [currentAchievementSort, setCurrentAchievementSort] = useAtom(currentAchievementSortAtom);
  const [isLockedOnlyFilterEnabled, setIsLockedOnlyFilterEnabled] = useAtom(
    isLockedOnlyFilterEnabledAtom,
  );
  const [isMissableOnlyFilterEnabled, setIsMissableOnlyFilterEnabled] = useAtom(
    isMissableOnlyFilterEnabledAtom,
  );

  const handleToggleLockedOnlyFilter = (pressed: boolean) => {
    setIsLockedOnlyFilterEnabled(pressed);
    lockedOnlyCookie.toggleGameId(pressed);
  };

  const handleToggleMissableOnlyFilter = (pressed: boolean) => {
    setIsMissableOnlyFilterEnabled(pressed);
    missableOnlyCookie.toggleGameId(pressed);
  };

  return (
    <div className="-mt-1.5 flex w-full items-center justify-between rounded bg-embed px-2 py-1.5">
      <AchievementSortButton
        value={currentAchievementSort}
        onChange={(newValue) => setCurrentAchievementSort(newValue)}
        availableSortOrders={[
          'normal',
          '-normal',
          'wonBy',
          '-wonBy',
          'points',
          '-points',
          'title',
          '-title',
          'type',
          '-type',
        ]}
      />

      <div className="flex gap-2">
        {lockedAchievementsCount ? (
          <BaseToggle
            size="sm"
            className="flex h-[30px] items-center gap-1 !text-[13px] lg:active:translate-y-[1px] lg:active:scale-[0.98]"
            variant="outline"
            pressed={isLockedOnlyFilterEnabled}
            onPressedChange={handleToggleLockedOnlyFilter}
          >
            <LuEyeOff className="-mt-0.5" />
            <span>{t('Locked Only')}</span>
          </BaseToggle>
        ) : null}

        {missableAchievementsCount ? (
          <BaseToggle
            size="sm"
            className="group flex h-[30px] items-center gap-0.5 !text-[13px] lg:active:translate-y-[1px] lg:active:scale-[0.98]"
            variant="outline"
            pressed={isMissableOnlyFilterEnabled}
            onPressedChange={handleToggleMissableOnlyFilter}
          >
            <IoAlert className="-mt-0.5 size-4" />
            <span>{t('Missable Only')}</span>

            <BaseChip
              className={cn([
                'ml-1.5 bg-neutral-950 px-2 text-neutral-300 opacity-50 transition',
                'group-hover:opacity-100 light:border-neutral-500 light:text-neutral-800',
                isMissableOnlyFilterEnabled ? 'opacity-100' : null,
              ])}
            >
              {formatNumber(missableAchievementsCount)}
            </BaseChip>
          </BaseToggle>
        ) : null}
      </div>
    </div>
  );
};
