import type { FC, ReactNode } from 'react';

import { baseChipVariants } from '@/common/components/+vendor/BaseChip';
import { cn } from '@/common/utils/cn';

interface SelectableChipProps {
  children: ReactNode;
  isSelected: boolean;

  onClick?: () => void;
}

export const SelectableChip: FC<SelectableChipProps> = ({ children, isSelected, onClick }) => {
  return (
    <button
      aria-pressed={isSelected}
      onClick={onClick}
      className={baseChipVariants({
        className: cn(
          'border transition',
          isSelected
            ? 'border-neutral-200 !bg-neutral-800 text-neutral-50'
            : 'border-neutral-700 text-neutral-300 hover:bg-neutral-800 light:bg-neutral-100 light:text-neutral-700',
        ),
      })}
    >
      {children}
    </button>
  );
};
