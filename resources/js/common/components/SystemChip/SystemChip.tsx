import type { FC } from 'react';

import { cn } from '@/utils/cn';

// Re-use the System type to allow for prop spreading.
// Extend as necessary with `&`, see `GameAvatarProps`.
type SystemChipProps = App.Platform.Data.System & { className?: string };

export const SystemChip: FC<SystemChipProps> = ({ iconUrl, nameShort, className }) => {
  if (!nameShort || !iconUrl) {
    throw new Error('system.nameShort and system.iconUrl are required');
  }

  return (
    <span
      className={cn(
        'flex max-w-fit items-center gap-1 rounded-full bg-zinc-950/60 px-2.5 py-0.5 text-xs light:bg-neutral-50',
        className,
      )}
    >
      {iconUrl ? <img src={iconUrl} alt={nameShort} width={18} height={18} /> : null}

      {nameShort}
    </span>
  );
};
