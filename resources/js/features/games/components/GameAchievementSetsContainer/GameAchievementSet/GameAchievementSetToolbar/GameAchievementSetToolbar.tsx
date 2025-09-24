import { useAtom, useSetAtom } from 'jotai';
import { type FC, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { IoAlert } from 'react-icons/io5';
import { LuEyeOff } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { BaseToggle } from '@/common/components/+vendor/BaseToggle';
import { PlayableListSortButton } from '@/common/components/PlayableListSortButton';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { usePersistedGameIdsCookie } from '@/features/games/hooks/usePersistedGameIdsCookie';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
  userAchievementListChangeCounterAtom,
} from '@/features/games/state/games.atoms';

import { GameListViewSelectButton } from './GameListViewSelectButton';

interface GameAchievementSetToolbarProps {
  lockedAchievementsCount: number;
  missableAchievementsCount: number;
  unlockedAchievementsCount: number;
}

export const GameAchievementSetToolbar: FC<GameAchievementSetToolbarProps> = ({
  lockedAchievementsCount,
  missableAchievementsCount,
  unlockedAchievementsCount,
}) => {
  const { backingGame, numLeaderboards } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();
  const { formatNumber } = useFormatNumber();

  const lockedOnlyCookie = usePersistedGameIdsCookie(
    'hide_unlocked_achievements_games',
    backingGame.id,
  );
  const missableOnlyCookie = usePersistedGameIdsCookie(
    'hide_nonmissable_achievements_games',
    backingGame.id,
  );

  const [currentListView, setCurrentListView] = useAtom(currentListViewAtom);
  const [currentAchievementSort, setCurrentAchievementSort] = useAtom(currentPlayableListSortAtom);
  const [isLockedOnlyFilterEnabled, setIsLockedOnlyFilterEnabled] = useAtom(
    isLockedOnlyFilterEnabledAtom,
  );
  const [isMissableOnlyFilterEnabled, setIsMissableOnlyFilterEnabled] = useAtom(
    isMissableOnlyFilterEnabledAtom,
  );
  const setUserAchievementListChangeCounter = useSetAtom(userAchievementListChangeCounterAtom);

  const canShowUnlockStatusSortOrders =
    unlockedAchievementsCount > 0 && unlockedAchievementsCount < backingGame.achievementsPublished!;

  useEffect(() => {
    if (currentListView === 'leaderboards' && numLeaderboards === 0) {
      setCurrentListView('achievements');
    }
  }, [currentListView, numLeaderboards, setCurrentListView]);

  const handleToggleLockedOnlyFilter = (pressed: boolean) => {
    setIsLockedOnlyFilterEnabled(pressed);
    lockedOnlyCookie.toggleGameId(pressed);

    setUserAchievementListChangeCounter((prev) => prev + 1);
  };

  const handleToggleMissableOnlyFilter = (pressed: boolean) => {
    setIsMissableOnlyFilterEnabled(pressed);
    missableOnlyCookie.toggleGameId(pressed);

    setUserAchievementListChangeCounter((prev) => prev + 1);
  };

  return (
    <div
      data-testid="game-achievement-set-toolbar"
      className={cn(
        '-mt-1.5 flex w-full flex-col items-center justify-between gap-2 rounded bg-embed px-2 py-1.5 sm:flex-row',
        'light:border light:border-neutral-200 light:bg-white',
      )}
    >
      <div className="flex w-full gap-2 sm:w-auto">
        <PlayableListSortButton
          value={currentAchievementSort}
          onChange={(newValue) => {
            setCurrentAchievementSort(newValue);

            setUserAchievementListChangeCounter((prev) => prev + 1);
          }}
          availableSortOrders={
            currentListView === 'achievements'
              ? [
                  // Only show the "Unlocked first" option when the user has unlocked some (but not all) achievements.
                  ...(canShowUnlockStatusSortOrders ? ['normal' as const] : []),

                  'displayOrder',
                  '-displayOrder',
                  'wonBy',
                  '-wonBy',
                  'points',
                  '-points',
                  'title',
                  '-title',
                  'type',
                  '-type',
                ]
              : ['displayOrder', '-displayOrder', 'title', '-title']
          }
          buttonClassName="w-full sm:w-auto"
        />

        {numLeaderboards > 0 ? <GameListViewSelectButton /> : null}
      </div>

      {missableAchievementsCount || (lockedAchievementsCount && unlockedAchievementsCount) ? (
        <div className="flex w-full gap-2 sm:w-auto">
          {missableAchievementsCount ? (
            <BaseToggle
              size="sm"
              className={cn([
                'group flex h-[30px] w-full items-center gap-1 !text-[13px] sm:w-auto lg:active:translate-y-[1px] lg:active:scale-[0.98]',
                'light:bg-white light:hover:bg-neutral-50 light:hover:text-neutral-700',
                'data-[state=on]:light:border-neutral-700 data-[state=on]:light:bg-neutral-50 data-[state=on]:light:text-neutral-900',
              ])}
              variant="outline"
              pressed={isMissableOnlyFilterEnabled}
              onPressedChange={handleToggleMissableOnlyFilter}
              disabled={currentListView !== 'achievements'}
            >
              <IoAlert className="-mt-0.5 size-4" />
              <span className="whitespace-nowrap">{t('Missable Only')}</span>

              <BaseChip
                className={cn([
                  'ml-1.5 bg-neutral-950 px-2 text-neutral-300 opacity-50 transition',
                  'group-hover:opacity-100 light:border-neutral-500 light:text-neutral-800',
                  'w-full sm:w-auto',
                  isMissableOnlyFilterEnabled ? 'opacity-100' : null,
                ])}
              >
                {formatNumber(missableAchievementsCount)}
              </BaseChip>
            </BaseToggle>
          ) : null}

          {lockedAchievementsCount && unlockedAchievementsCount ? (
            <BaseToggle
              size="sm"
              className={cn([
                'flex h-[30px] items-center gap-1 whitespace-nowrap !text-[13px] lg:active:translate-y-[1px] lg:active:scale-[0.98]',
                'light:bg-white light:hover:bg-neutral-50 light:hover:text-neutral-700',
                'data-[state=on]:light:border-neutral-700 data-[state=on]:light:bg-neutral-50 data-[state=on]:light:text-neutral-900',
                'w-full sm:w-auto',
              ])}
              variant="outline"
              pressed={isLockedOnlyFilterEnabled}
              onPressedChange={handleToggleLockedOnlyFilter}
              disabled={currentListView !== 'achievements'}
            >
              <LuEyeOff className="-mt-0.5" />
              <span>{t('Locked Only')}</span>
            </BaseToggle>
          ) : null}
        </div>
      ) : null}
    </div>
  );
};
