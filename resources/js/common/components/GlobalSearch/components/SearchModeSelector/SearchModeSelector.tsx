import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { SelectableChip } from '@/common/components/SelectableChip';

import type { SearchMode } from '../../models';

interface SearchModeSelectorProps {
  onChange: (value: SearchMode) => void;
  selectedMode: SearchMode;
}

export const SearchModeSelector: FC<SearchModeSelectorProps> = ({ onChange, selectedMode }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-2">
      <SelectableChip isSelected={selectedMode === 'all'} onClick={() => onChange('all')}>
        {t('searchFilterAll')}
      </SelectableChip>

      <SelectableChip isSelected={selectedMode === 'games'} onClick={() => onChange('games')}>
        {t('Games')}
      </SelectableChip>

      <SelectableChip isSelected={selectedMode === 'hubs'} onClick={() => onChange('hubs')}>
        {t('Hubs')}
      </SelectableChip>

      <SelectableChip isSelected={selectedMode === 'users'} onClick={() => onChange('users')}>
        {t('Users')}
      </SelectableChip>

      <SelectableChip isSelected={selectedMode === 'events'} onClick={() => onChange('events')}>
        {t('Events')}
      </SelectableChip>

      <SelectableChip
        isSelected={selectedMode === 'achievements'}
        onClick={() => onChange('achievements')}
      >
        {t('Achievements')}
      </SelectableChip>
    </div>
  );
};
