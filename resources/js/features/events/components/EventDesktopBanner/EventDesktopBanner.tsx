import { AnimatePresence, motion } from 'motion/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCalendarRange, LuWrench } from 'react-icons/lu';
import { route } from 'ziggy-js';

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

// Status chips are not links, so they suppress the link-like hover styles.
const statusChipClassNames = cn(
  responsiveHeaderChipClassNames,
  'font-medium text-neutral-300 sm:text-sm light:text-neutral-700',
  'sm:hover:border-white/20 sm:hover:bg-black/70',
  'light:sm:hover:bg-white/80',
);

export const EventDesktopBanner: FC = () => {
  const { breadcrumbs, can, event } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const [isManageHovered, setIsManageHovered] = useState(false);

  const legacyGame = event.legacyGame!;

  // Find the event category hub (Community Events or Developer Events) from the breadcrumbs.
  const categoryHubIds = Object.values(EventCategoryHubIds) as number[];
  const categoryHubId =
    breadcrumbs?.find((b) => categoryHubIds.includes(b.id))?.id ??
    EventCategoryHubIds.CommunityEvents;
  const categoryHubTitle =
    categoryHubId === EventCategoryHubIds.DeveloperEvents
      ? t('Developer Events')
      : t('Community Events');

  // When concluded with both dates, show a single date range chip instead of separate chips.
  const isConcludedWithDateRange =
    event.state === 'concluded' && !!event.activeFrom && !!event.activeThrough;

  return (
    <div
      data-testid="desktop-banner"
      className={cn(
        'relative overflow-hidden',
        'h-[13.25rem] md:-mt-[44px]',
        'border-b border-neutral-700',
        'ml-[calc(50%-50vw)] w-screen',
        'lg:h-[212px]',
      )}
      style={{ background: '#0a0a0a' }}
    >
      {/* Color-extracted blurred screenshot. */}
      <div className="absolute inset-0">
        <img
          src={legacyGame.imageIngameUrl}
          alt=""
          aria-hidden="true"
          className="h-full w-full object-cover"
          style={{
            filter: 'blur(80px) saturate(1.8)',
            transform: 'scale(4)',
          }}
          data-testid="fallback-color-source"
        />
      </div>

      <div className="absolute inset-0 bg-black/35 light:bg-black/25" />
      <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />

      {/* Noise texture to reduce gradient banding. */}
      <div
        className="pointer-events-none absolute inset-0 z-[2] hidden md:block"
        style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")`,
          opacity: 0.15,
          mixBlendMode: 'overlay',
        }}
      />

      {/* Event info and associated controls. */}
      <div
        className={cn(
          'absolute inset-x-0 bottom-0 z-[19] mx-auto max-w-screen-xl px-3 pb-4 transition-[padding]',
          'sm:px-4 md:px-6 md:pb-[46px] xl:px-0',
        )}
      >
        <div className="flex w-full flex-col gap-5 sm:gap-4 md:flex-row md:items-end">
          {/* Event badge. */}
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
            {/* Event title. */}
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

            {/* Parent hub chip, event status chips, and manage button. */}
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

      {/* Gradient overlay for text readability. */}
      <div
        className={cn(
          'absolute inset-0 md:hidden',
          'bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black',
          'light:from-black/20 light:via-black/30 light:to-black/50',
        )}
      />
    </div>
  );
};
