import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';
import { route } from 'ziggy-js';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { InertiaLink } from '@/common/components/InertiaLink';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { cleanEventAwardLabel } from '../../utils/cleanEventAwardLabel';

interface AwardTierItemProps {
  event: App.Platform.Data.Event;
  eventAward: App.Platform.Data.EventAward;
  hasVirtualTier: boolean;
}

export const AwardTierItem: FC<AwardTierItemProps> = ({ event, eventAward, hasVirtualTier }) => {
  const { t } = useTranslation();

  const earnersMessage = useEarnersMessage(!!eventAward.earnedAt, eventAward.badgeCount!);

  // Clean the award label by removing the event title prefix if present.
  const cleanedAwardLabel = cleanEventAwardLabel(eventAward.label, event);

  const areAllAchievementsOnePoint = event.eventAchievements?.every(
    (ea) => ea.achievement?.points && ea.achievement.points === 1,
  );

  const awardEarnersLink = getAwardEarnersLink(eventAward);

  const ConditionalLink = awardEarnersLink !== undefined ? InertiaLink : 'div';

  return (
    <div
      className={cn(
        'group rounded-lg p-1 light:bg-white',
        eventAward.earnedAt
          ? 'bg-zinc-700/50 light:border light:border-yellow-500 light:bg-white'
          : 'bg-zinc-800/50 light:bg-zinc-100',
      )}
    >
      <ConditionalLink href={awardEarnersLink as string}>
        <div className="relative flex items-center gap-3">
          <div className="relative">
            <img
              src={eventAward.badgeUrl}
              alt={eventAward.label}
              className={cn(
                'size-12 rounded-sm transition',
                eventAward.earnedAt
                  ? 'opacity-100 outline outline-2 outline-offset-1 outline-[gold]'
                  : 'opacity-50 group-hover:opacity-100',
              )}
            />
          </div>

          <div className="flex w-full items-center justify-between gap-2">
            <div className="flex flex-col">
              <div className="flex items-center gap-2">
                {!hasVirtualTier ? (
                  <>
                    <p
                      data-testid="award-tier-label"
                      className={cn([
                        'flex gap-2 text-xs font-medium',
                        awardEarnersLink !== undefined
                          ? 'transition group-hover:text-link-hover'
                          : null,
                      ])}
                    >
                      {cleanedAwardLabel}
                    </p>

                    <span className="whitespace-nowrap rounded bg-white/5 px-1.5 text-2xs text-neutral-400 light:bg-neutral-100 light:text-neutral-600">
                      {t(
                        areAllAchievementsOnePoint
                          ? '{{val, number}} achievements'
                          : '{{val, number}} points',
                        {
                          val: eventAward.pointsRequired,
                          count: eventAward.pointsRequired,
                        },
                      )}
                    </span>
                  </>
                ) : null}
              </div>

              <p className="text-2xs text-neutral-500">{earnersMessage}</p>
            </div>

            {eventAward.earnedAt ? (
              <BaseTooltip>
                <BaseTooltipTrigger>
                  <div
                    data-testid="award-earned-checkmark"
                    className={cn([
                      'mr-1 flex size-6 items-center justify-center rounded-full bg-embed',
                      'light:bg-neutral-200 light:text-neutral-700',
                      awardEarnersLink !== undefined
                        ? 'transition group-hover:text-link-hover'
                        : null,
                    ])}
                  >
                    <LuCheck className="size-4" />
                  </div>
                </BaseTooltipTrigger>

                <BaseTooltipContent>
                  {t('Awarded {{awardedDate}}', {
                    awardedDate: formatDate(eventAward.earnedAt, 'lll'),
                  })}
                </BaseTooltipContent>
              </BaseTooltip>
            ) : null}
          </div>
        </div>
      </ConditionalLink>
    </div>
  );
};

function getAwardEarnersLink(eventAward: App.Platform.Data.EventAward): string | undefined {
  if (eventAward.badgeCount! < 1) {
    return;
  }

  if (eventAward.tierIndex > 0) {
    return route('event.award-earners.index', {
      event: eventAward.eventId,
      tier: eventAward.tierIndex,
    });
  }

  return route('event.award-earners.index', { event: eventAward.eventId });
}

function useEarnersMessage(didCurrentUserEarn: boolean, totalEarners: number): string {
  const { t } = useTranslation();

  if (!didCurrentUserEarn) {
    return t('{{val, number}} players have earned this', {
      val: totalEarners,
      count: totalEarners,
    });
  }

  if (didCurrentUserEarn && totalEarners === 1) {
    return t('You are the only player to earn this');
  }

  return t('Earned by you and {{val, number}} other players', {
    val: totalEarners - 1,
    count: totalEarners - 1,
  });
}
