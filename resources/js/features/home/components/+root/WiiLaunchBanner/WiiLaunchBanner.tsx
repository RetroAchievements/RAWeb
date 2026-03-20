import type { FC } from 'react';
import { LuChevronRight } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

// TODO remove after Tues Mar 24

export const WiiLaunchBanner: FC = () => {
  const { wiiSetCount } = usePageProps<App.Http.Data.HomePageProps>();

  if (!wiiSetCount) {
    return null;
  }

  return (
    <InertiaLink
      href={route('system.game.index', { system: 19 })}
      className="group flex items-center gap-3 rounded-lg border border-neutral-700 bg-embed p-3 light:border-neutral-300 light:bg-neutral-50 lg:hover:border-neutral-500 lg:hover:light:border-neutral-400"
    >
      <img
        src="/assets/images/system/wii.png"
        alt="Wii"
        width={22}
        height={22}
        className="rounded-sm"
      />

      <p className="text-sm">{`The Wii is here! Explore all ${wiiSetCount} achievement sets.`}</p>

      <LuChevronRight className="ml-auto hidden size-4 min-w-4 transition lg:inline-block lg:group-hover:translate-x-0.5" />
    </InertiaLink>
  );
};
