import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { SetSelectionDropdown } from './SetSelectionDropdown';
import { SetSelectionTabs } from './SetSelectionTabs';

interface SetSelectionControlProps {
  activeTab: number | null;
}

export const SetSelectionControl: FC<SetSelectionControlProps> = ({ activeTab }) => {
  const { selectableGameAchievementSets, ziggy } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  // On mobile with 5+ sets, use a dropdown for better UX.
  if (ziggy.device === 'mobile' && selectableGameAchievementSets.length >= 5) {
    return <SetSelectionDropdown activeTab={activeTab} />;
  }

  // Otherwise, use the tab interface.
  return <SetSelectionTabs activeTab={activeTab} />;
};
