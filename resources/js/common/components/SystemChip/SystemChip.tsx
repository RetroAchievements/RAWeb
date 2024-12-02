import type { FC, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

import { BaseChip } from '../+vendor/BaseChip';

// Re-use the System type to allow for prop spreading.
// Extend as necessary with `&`, see `GameAvatarProps`.
type SystemChipProps = App.Platform.Data.System & {
  children?: ReactNode;
  className?: string;
  showLabel?: boolean;
};

export const SystemChip: FC<SystemChipProps> = ({
  iconUrl,
  nameShort,
  children,
  className,
  showLabel = true,
}) => {
  if (!nameShort || !iconUrl) {
    throw new Error('system.nameShort and system.iconUrl are required');
  }

  return (
    <BaseChip className={className}>
      <img src={iconUrl} alt={nameShort} width={18} height={18} />

      <span className={cn(showLabel ? null : 'sr-only')}>{children ?? nameShort}</span>
    </BaseChip>
  );
};
