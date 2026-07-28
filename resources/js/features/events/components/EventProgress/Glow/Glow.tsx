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
        'opacity-75 blur-sm transition-all duration-1000 group-hover:opacity-100 group-hover:duration-200',
        'bg-linear-to-tr',
        'motion-safe:animate-tilt',

        isMastered ? 'inset-1.75 light:inset-3' : 'inset-1.25 light:inset-3',
        isMastered
          ? 'group-hover:inset-1.25 light:group-hover:inset-3'
          : 'group-hover:inset-0.75 light:group-hover:inset-3',
        isMastered
          ? 'from-yellow-400 to-amber-400'
          : 'from-zinc-400 to-slate-500 light:from-zinc-600 light:to-slate-700',
      ])}
    />
  );
};
