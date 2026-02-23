import { AnimatePresence, motion } from 'motion/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCalendarRange, LuWrench } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { DesktopBanner } from '@/common/components/DesktopBanner';
import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { responsiveHeaderChipClassNames } from '@/common/utils/responsiveHeaderChipClassNames';

import { EndDateChip } from '../EventHeader/EndDateChip';
import { IsPlayableChip } from '../EventHeader/IsPlayableChip';
import { StartDateChip } from '../EventHeader/StartDateChip';

const EventCategoryHubIds = {
  CommunityEvents: 4,
  DeveloperEvents: 5,
} as const;

// Status chips aren't links, so we suppress the link-like hover styles from the base chip.
const statusChipClassNames = cn(
  responsiveHeaderChipClassNames,
  'font-medium text-neutral-300 sm:text-sm light:text-neutral-700',
  'sm:hover:border-white/20 sm:hover:bg-black/70',
  'light:sm:hover:bg-white/80',
);

export const EventDesktopBanner: FC = () => {
  const { banner, breadcrumbs, can, event } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const [isManageHovered, setIsManageHovered] = useState(false);

  const legacyGame = event.legacyGame!;

  // Determine which event category to display based on the hub breadcrumb trail.
  const categoryHubIds = Object.values(EventCategoryHubIds) as number[];
  const categoryHubId =
    breadcrumbs?.find((b) => categoryHubIds.includes(b.id))?.id ??
    EventCategoryHubIds.CommunityEvents;
  const categoryHubTitle =
    categoryHubId === EventCategoryHubIds.DeveloperEvents
      ? t('Developer Events')
      : t('Community Events');

  // Concluded events with both dates use a combined range chip to save horizontal space.
  const isConcludedWithDateRange =
    event.state === 'concluded' && !!event.activeFrom && !!event.activeThrough;

  return (
    <DesktopBanner banner={banner}>
      {/* Event info and associated controls. */}
      <div
        className={cn(
          'absolute inset-x-0 bottom-0 z-[19] mx-auto max-w-screen-xl px-3 pb-4 transition-[padding]',
          'sm:px-4 md:px-6 md:pb-[46px] xl:px-0',
        )}
      >
        <div className="flex w-full flex-col gap-5 sm:gap-4 md:flex-row md:items-end">
          <img
            loading="eager"
            decoding="sync"
            fetchPriority="high"
            width="96"
            height="96"
            src={legacyGame.badgeUrl}
            alt={legacyGame.title}
            className={cn(
              'size-20 rounded bg-neutral-950/50 object-cover md:size-24',
              'ring-1 ring-white/20 ring-offset-2 ring-offset-black/50',
              'shadow-md shadow-black/50 md:shadow-xl',
              'light:bg-white/50 light:shadow-black/20 light:ring-black/20 light:ring-offset-white/50',
            )}
          />

          <div className="flex w-full flex-col gap-1 md:gap-2">
            {/* Event title */}
            <h1
              className={cn(
                'w-fit font-bold leading-tight text-white',
                '[text-shadow:_0_1px_2px_rgb(0_0_0),_0_2px_6px_rgb(0_0_0_/_80%),_0_0_14px_rgb(0_0_0_/_60%)]',
                'text-2xl md:text-3xl',
                legacyGame.title.length > 26 ? '!text-xl md:!text-2xl' : null,
                legacyGame.title.length > 30 ? '!text-base md:!text-xl' : null,
                legacyGame.title.length > 50 ? 'line-clamp-2 !text-sm md:!text-xl' : null,
              )}
            >
              <GameTitle title={legacyGame.title} />
            </h1>

            {/* Parent hub chip, event status chips, and manage button */}
            <div className="flex w-full justify-between gap-2">
              <div className="flex flex-wrap items-center gap-2">
                <InertiaLink
                  href={route('hub.show', { gameSet: categoryHubId })}
                  className={responsiveHeaderChipClassNames}
                >
                  <img
                    src="/assets/images/system/events.png"
                    alt={t('Event')}
                    className="size-4 sm:size-[18px]"
                  />

                  <span className="text-xs font-medium sm:text-sm">
                    <span className="sm:hidden">{t('Event')}</span>
                    <span className="hidden sm:inline">{categoryHubTitle}</span>
                  </span>
                </InertiaLink>

                <IsPlayableChip event={event} className={statusChipClassNames} />

                {isConcludedWithDateRange ? (
                  <span className={statusChipClassNames}>
                    <LuCalendarRange className="size-4" />
                    <span className="text-xs font-medium sm:text-sm">
                      {formatDate(event.activeFrom!, 'll')} {'–'}{' '}
                      {formatDate(event.activeThrough!, 'll')}
                    </span>
                  </span>
                ) : (
                  <>
                    <StartDateChip event={event} className={statusChipClassNames} />
                    <EndDateChip event={event} className={statusChipClassNames} />
                  </>
                )}
              </div>

              {can.manageEvents ? (
                <a
                  href={`/manage/events/${event.id}`}
                  target="_blank"
                  aria-label={t('Manage')}
                  className={cn(responsiveHeaderChipClassNames, '!gap-0 !rounded-full !px-2.5')}
                  onMouseEnter={() => setIsManageHovered(true)}
                  onMouseLeave={() => setIsManageHovered(false)}
                >
                  <LuWrench className="size-3.5 sm:size-4" />

                  <AnimatePresence>
                    {isManageHovered ? (
                      <motion.span
                        initial={{ width: 0, opacity: 0, marginLeft: 0 }}
                        animate={{ width: 'auto', opacity: 1, marginLeft: 6 }}
                        exit={{ width: 0, opacity: 0, marginLeft: 0 }}
                        transition={{ type: 'spring', duration: 0.3, bounce: 0 }}
                        className="overflow-hidden whitespace-nowrap text-xs font-medium sm:text-sm"
                      >
                        {t('Manage')}
                      </motion.span>
                    ) : null}
                  </AnimatePresence>
                </a>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </DesktopBanner>
  );
};
