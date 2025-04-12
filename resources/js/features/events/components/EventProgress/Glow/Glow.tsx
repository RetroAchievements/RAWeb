import type { FC } from 'react';

import { cn } from '@/common/utils/cn';

interface GlowProps {
  isMastered: boolean;
}

export const Glow: FC<GlowProps> = ({ isMastered }) => {
  return (
    <div
      data-testid="progress-blur"
      className={cn([
        'absolute rounded-lg',
        'opacity-75 blur transition-all duration-1000 group-hover:opacity-100 group-hover:duration-200',
        'bg-gradient-to-tr',
        'motion-safe:animate-tilt',

        isMastered ? 'inset-[7px] light:inset-[12px]' : 'inset-[5px] light:inset-[12px]',
        isMastered
          ? 'group-hover:inset-[5px] light:group-hover:inset-[12px]'
          : 'group-hover:inset-[3px] light:group-hover:inset-[12px]',
        isMastered
          ? 'from-yellow-400 to-amber-400'
          : 'from-zinc-400 to-slate-500 light:from-zinc-600 light:to-slate-700',
      ])}
    />
  );
};
