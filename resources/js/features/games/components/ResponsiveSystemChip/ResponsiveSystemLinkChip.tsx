import type { FC } from 'react';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { responsiveHeaderChipClassNames } from '@/common/utils/responsiveHeaderChipClassNames';

export const ResponsiveSystemLinkChip: FC = () => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <InertiaLink
      href={route('system.game.index', { system: game.system!.id })}
      className={responsiveHeaderChipClassNames}
      prefetch="desktop-hover-only"
    >
      <img
        src={game.system?.iconUrl}
        alt={game.system?.nameShort}
        className="size-4 sm:size-[18px]"
      />

      <span className="text-xs font-medium sm:text-sm">
        <span className="sm:hidden">{game.system?.nameShort}</span>
        <span className="hidden sm:inline">{game.system?.name}</span>
      </span>
    </InertiaLink>
  );
};
