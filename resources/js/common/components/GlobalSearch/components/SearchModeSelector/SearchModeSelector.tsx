import { motion } from 'motion/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronRight } from 'react-icons/lu';

import { SelectableChip } from '@/common/components/SelectableChip';

import type { SearchMode } from '../../models';

interface SearchModeSelectorProps {
  onChange: (value: SearchMode) => void;
  rawQuery: string;
  selectedMode: SearchMode;
}

export const SearchModeSelector: FC<SearchModeSelectorProps> = ({
  onChange,
  rawQuery,
  selectedMode,
}) => {
  const { t } = useTranslation();

  const modes: Array<{ value: SearchMode; label: string }> = [
    { value: 'all', label: t('searchFilterAll') }, // "All" means different things based on context. Use a unique key.
    { value: 'games', label: t('Games') },
    { value: 'hubs', label: t('Hubs') },
    { value: 'users', label: t('Users') },
    { value: 'events', label: t('Events') },
    { value: 'achievements', label: t('Achievements') },
  ];

  return (
    <div className="flex flex-wrap items-center gap-2">
      {modes.map((mode, index) => (
        <motion.div
          key={mode.value}
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{
            duration: 0.15,
            delay: index * 0.02,
          }}
        >
          <SelectableChip
            isSelected={selectedMode === mode.value}
            onClick={() => onChange(mode.value)}
          >
            <motion.span
              animate={{
                scale: selectedMode === mode.value ? 1.05 : 1,
              }}
              transition={{ duration: 0.1 }}
            >
              {mode.label}
            </motion.span>
          </SelectableChip>
        </motion.div>
      ))}

      <motion.a
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.15, delay: (modes.length + 1) * 0.02 }}
        href={rawQuery ? `/searchresults.php?s=${rawQuery}` : '/searchresults.php'}
        className="flex items-center sm:hidden"
      >
        {t('Browse')} <LuChevronRight className="size-4" />
      </motion.a>
    </div>
  );
};
