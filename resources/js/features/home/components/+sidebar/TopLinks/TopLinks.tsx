import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import {
  LuBook,
  LuCircleDot,
  LuCircleHelp,
  LuDownload,
  LuNewspaper,
  LuPodcast,
} from 'react-icons/lu';
import { PiMedalFill } from 'react-icons/pi';
import { SiDiscord, SiPatreon } from 'react-icons/si';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

export const TopLinks: FC = () => {
  const { config } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-2.5">
      <a
        href="/download.php"
        className={cn(
          baseButtonVariants({ size: 'sm' }),
          buildTrackingClassNames('Click Top Link Download Emulator'),
        )}
      >
        <LuDownload className="mr-2 h-4 w-4 text-sky-400" />
        {t('Download Emulator')}
      </a>

      <div className="flex flex-col gap-2.5 sm:grid sm:grid-cols-2 lg:flex">
        <a
          href="/globalRanking.php"
          className={cn(baseButtonVariants({ size: 'sm' }), 'Click Top Link Global Points Ranking')}
        >
          <PiMedalFill className="mr-2 h-4 w-4 text-amber-400" />
          {t('Global Points Ranking')}
        </a>

        <a
          href={route('ranking.beaten-games')}
          className={cn(
            baseButtonVariants({ size: 'sm' }),
            'Click Top Link Global Beaten Games Ranking',
          )}
        >
          <LuCircleDot className="mr-2 h-4 w-4 text-amber-400" />
          {t('Global Beaten Games Ranking')}
        </a>
      </div>

      <div className="flex flex-col gap-2.5 sm:grid sm:grid-cols-2 lg:flex">
        <a
          href="https://discord.com/invite/retroachievements"
          className={baseButtonVariants({ size: 'sm' })}
        >
          <SiDiscord
            className={cn(
              'mr-2 h-4 w-4 text-[#7289DA]',
              buildTrackingClassNames('Click Top Link Join Us On Discord'),
            )}
          />
          {t('Join us on Discord')}
        </a>

        {config?.services.patreon.userId ? (
          <a
            href={`https://www.patreon.com/bePatron?u=${config.services.patreon.userId}`}
            className={baseButtonVariants({ size: 'sm' })}
          >
            <SiPatreon
              className={cn(
                'mr-2 h-4 w-4 text-[#F96854]',
                buildTrackingClassNames('Click Top Link Become a Patron'),
              )}
            />
            {t('Become a Patron')}
          </a>
        ) : null}
      </div>

      <div className="grid grid-cols-2 gap-2.5">
        <a
          href="https://news.retroachievements.org"
          className={cn(
            baseButtonVariants({ size: 'sm' }),
            buildTrackingClassNames('Click Top Link RANews'),
          )}
        >
          <LuNewspaper className="mr-2 h-4 w-4 text-blue-400" />
          {/* Label should not change regardless of locale */}
          {'RANews'}
        </a>

        <a
          href="https://www.youtube.com/@RAPodcast"
          className={cn(
            baseButtonVariants({ size: 'sm' }),
            buildTrackingClassNames('Click Top Link RAPodcast'),
          )}
        >
          <LuPodcast className="mr-2 h-4 w-4 text-blue-400" />
          {/* Label should not change regardless of locale */}
          {'RAPodcast'}
        </a>
      </div>

      <div className="grid grid-cols-5 gap-2">
        <a
          href="https://docs.retroachievements.org"
          className={cn(
            baseButtonVariants({ size: 'sm', className: 'col-span-3' }),
            buildTrackingClassNames('Click Top Link Documentation'),
          )}
        >
          <LuBook className="mr-2 h-4 w-4 text-blue-400" />
          {t('Documentation')}
        </a>

        <a
          href="https://docs.retroachievements.org/general/faq.html"
          className={cn(
            baseButtonVariants({ size: 'sm', className: 'col-span-2' }),
            buildTrackingClassNames('Click Top Link FAQ'),
          )}
        >
          <LuCircleHelp className="mr-2 h-4 w-4 text-blue-400" />
          {t('FAQ')}
        </a>
      </div>
    </div>
  );
};
