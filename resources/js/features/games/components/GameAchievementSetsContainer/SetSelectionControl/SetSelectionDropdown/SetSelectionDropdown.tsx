import { router } from '@inertiajs/react';
import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { route } from 'ziggy-js';

import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';
import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

interface SetSelectionDropdownProps {
  activeTab: number | null;
}

export const SetSelectionDropdown: FC<SetSelectionDropdownProps> = ({ activeTab }) => {
  const { game, selectableGameAchievementSets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const currentListView = useAtomValue(currentListViewAtom);

  // Determine the current value based on activeTab. Otherwise, default to the first set.
  const currentValue = activeTab
    ? String(activeTab)
    : String(selectableGameAchievementSets[0]?.achievementSet.id ?? '');

  const handleValueChange = (value: string) => {
    const selectedSet = selectableGameAchievementSets.find(
      (gas) => String(gas.achievementSet.id) === value,
    );

    router.visit(
      route('game.show', {
        game: game.id,
        set: selectedSet!.type === 'core' ? undefined : selectedSet!.achievementSet.id,
        view: currentListView === 'leaderboards' ? 'leaderboards' : undefined,
      }),
      {
        preserveScroll: true,
      },
    );
  };

  if (!selectableGameAchievementSets.length) {
    return null;
  }

  return (
    <BaseSelect value={currentValue} onValueChange={handleValueChange}>
      <BaseSelectTrigger className="w-full">
        <BaseSelectValue />
      </BaseSelectTrigger>

      <BaseSelectContent>
        {selectableGameAchievementSets.map((gas) => (
          <BaseSelectItem key={`dd-${gas.id}`} value={String(gas.achievementSet.id)}>
            <img
              src={gas.achievementSet.imageAssetPathUrl}
              alt={gas.title ?? BASE_SET_LABEL}
              className="size-6 select-none rounded-sm"
            />

            {gas.title ?? BASE_SET_LABEL}
          </BaseSelectItem>
        ))}
      </BaseSelectContent>
    </BaseSelect>
  );
};
