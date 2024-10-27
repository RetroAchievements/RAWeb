import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import {
  LuBook,
  LuCircleDot,
  LuDownload,
  LuHelpCircle,
  LuNewspaper,
  LuPodcast,
} from 'react-icons/lu';
import { PiMedalFill } from 'react-icons/pi';
import { SiDiscord, SiPatreon } from 'react-icons/si';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';

// TODO if config.services.discord.invite_id
// TODO if config.services.patreon.user_id
// TODO tracking

export const TopLinks: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="flex flex-col gap-2.5">
      <a href={route('download.index')} className={baseButtonVariants({ size: 'sm' })}>
        <LuDownload className="mr-2 h-4 w-4 text-sky-400" />
        {t('Download Emulator')}
      </a>

      <div className="flex flex-col gap-2.5 sm:grid sm:grid-cols-2 lg:flex">
        <a href="/globalRanking.php" className={baseButtonVariants({ size: 'sm' })}>
          <PiMedalFill className="mr-2 h-4 w-4 text-amber-400" />
          {t('Global Points Ranking')}
        </a>

        <a href={route('ranking.beaten-games')} className={baseButtonVariants({ size: 'sm' })}>
          <LuCircleDot className="mr-2 h-4 w-4 text-amber-400" />
          {t('Global Beaten Games Ranking')}
        </a>
      </div>

      <div className="flex flex-col gap-2.5 sm:grid sm:grid-cols-2 lg:flex">
        <BaseButton size="sm">
          <SiDiscord className="mr-2 h-4 w-4 text-[#7289DA]" />
          {t('Join us on Discord')}
        </BaseButton>

        <BaseButton size="sm">
          <SiPatreon className="mr-2 h-4 w-4 text-[#F96854]" />
          {t('Become a Patron')}
        </BaseButton>
      </div>

      <div className="grid grid-cols-2 gap-2.5">
        <a href="https://news.retroachievements.org" className={baseButtonVariants({ size: 'sm' })}>
          <LuNewspaper className="mr-2 h-4 w-4 text-blue-400" />
          {/* Label should not change regardless of locale */}
          {'RANews'}
        </a>

        <a href="https://www.youtube.com/@RAPodcast" className={baseButtonVariants({ size: 'sm' })}>
          <LuPodcast className="mr-2 h-4 w-4 text-blue-400" />
          {/* Label should not change regardless of locale */}
          {'RAPodcast'}
        </a>
      </div>

      <div className="grid grid-cols-5 gap-2">
        <a
          href="https://docs.retroachievements.org"
          className={baseButtonVariants({ size: 'sm', className: 'col-span-3' })}
        >
          <LuBook className="mr-2 h-4 w-4 text-blue-400" />
          {t('Documentation')}
        </a>

        <a
          href="https://docs.retroachievements.org/general/faq.html"
          className={baseButtonVariants({ size: 'sm', className: 'col-span-2' })}
        >
          <LuHelpCircle className="mr-2 h-4 w-4 text-blue-400" />
          {t('FAQ')}
        </a>
      </div>
    </div>
  );
};
