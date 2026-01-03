import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { SetSelectionDropdown } from './SetSelectionDropdown';
import { SetSelectionTabs } from './SetSelectionTabs';
import { SubsetConfigurationButton } from './SubsetConfigurationButton/SubsetConfigurationButton';

interface SetSelectionControlProps {
  activeTab: number | null;
}

export const SetSelectionControl: FC<SetSelectionControlProps> = ({ activeTab }) => {
  const { selectableGameAchievementSets, ziggy } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  // On mobile with 5+ sets, use a dropdown for better UX.
  if (ziggy.device === 'mobile' && selectableGameAchievementSets.length >= 5) {
    return (
      <div className="flex w-full items-center gap-2">
        <SetSelectionDropdown activeTab={activeTab} />
        <SubsetConfigurationButton />
      </div>
    );
  }

  // Otherwise, use the tab interface.
  return (
    <div className="flex w-full justify-between gap-2">
      <SetSelectionTabs activeTab={activeTab} />
      <SubsetConfigurationButton />
    </div>
  );
};
