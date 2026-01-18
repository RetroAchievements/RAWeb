import type { FC } from 'react';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const ResponsiveSystemLinkChip: FC = () => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <a
      href={route('system.game.index', { system: game.system!.id })}
      className={cn(
        'flex max-w-fit items-center rounded-full',
        'border bg-black/70 shadow-md backdrop-blur-sm',
        'gap-1 border-white/30 px-2.5 py-1',

        'sm:gap-1.5 sm:border-white/20 sm:px-3 sm:py-1.5',
        'sm:hover:border-link-hover sm:hover:bg-black/80',

        'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md',
        'light:sm:hover:bg-white/90',
      )}
    >
      <img
        src={game.system?.iconUrl}
        alt={game.system?.nameShort}
        width={16}
        height={16}
        className="sm:hidden"
      />
      <img
        src={game.system?.iconUrl}
        alt={game.system?.nameShort}
        width={18}
        height={18}
        className="hidden sm:block"
      />

      <span className="text-xs font-medium sm:hidden">{game.system?.nameShort}</span>
      <span className="hidden text-sm font-medium sm:inline">{game.system?.name}</span>
    </a>
  );
};
