import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCalendar, LuWrench } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface ClaimantsDisplayProps {
  achievementSetClaims: App.Platform.Data.AchievementSetClaim[];
}

export const ClaimantsDisplay: FC<ClaimantsDisplayProps> = ({ achievementSetClaims }) => {
  const canShowLastPlayedAt = achievementSetClaims.some((c) => !!c.userLastPlayedAt);

  return (
    <div
      className={cn(
        'flex items-center rounded-md bg-neutral-800/70 py-1 pr-2 text-neutral-400',
        'light:border light:border-neutral-200 light:bg-white light:text-neutral-600',
      )}
    >
      <ClaimsIcon achievementSetClaims={achievementSetClaims} />

      <UserAvatarStack
        users={achievementSetClaims.map((c) => c.user!)}
        maxVisible={999}
        size={20}
        isOverlappingAvatars={false}
      />

      {canShowLastPlayedAt ? <LastPlayedIcon achievementSetClaims={achievementSetClaims} /> : null}
    </div>
  );
};

interface LastPlayedIconProps {
  achievementSetClaims: App.Platform.Data.AchievementSetClaim[];
}

const LastPlayedIcon: FC<LastPlayedIconProps> = ({ achievementSetClaims }) => {
  const { t } = useTranslation();

  const { diffForHumans } = useDiffForHumans();

  const sortedClaims = [...achievementSetClaims].sort((a, b) => {
    // Sort by userLastPlayedAt in descending order (most recent first).
    return new Date(b.userLastPlayedAt!).getTime() - new Date(a.userLastPlayedAt!).getTime();
  });

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <LuCalendar className="ml-2 size-3.5 text-neutral-500 hover:text-neutral-300" />
      </BaseTooltipTrigger>

      <BaseTooltipContent className="whitespace-nowrap">
        <div className="flex flex-col gap-3">
          <TooltipCreditsSection headingLabel={t('Last Played')}>
            {sortedClaims.map((claim) => (
              <TooltipCreditRow
                key={`tooltip-claim-last-played-${claim.user!.displayName}`}
                credit={{
                  avatarUrl: claim.user!.avatarUrl,
                  count: 0, // noop
                  dateCredited: new Date().toISOString(), // noop
                  displayName: claim.user!.displayName,
                }}
              >
                {diffForHumans(claim.userLastPlayedAt!)}
              </TooltipCreditRow>
            ))}
          </TooltipCreditsSection>
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};

interface ClaimsIconProps {
  achievementSetClaims: App.Platform.Data.AchievementSetClaim[];
}

const ClaimsIcon: FC<ClaimsIconProps> = ({ achievementSetClaims }) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="flex items-center gap-1.5 px-2 py-[2.25px] text-neutral-300 light:text-neutral-700">
        <LuWrench className="size-3.5" />
        <span className="hidden pr-1 md:inline lg:hidden xl:inline">{t('Claimed by')}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="whitespace-nowrap">
        <div className="flex flex-col gap-3">
          <TooltipCreditsSection headingLabel={t('Active Claims')}>
            {achievementSetClaims.map((claim) => (
              <TooltipCreditRow
                key={`tooltip-claim-${claim.user!.displayName}`}
                credit={{
                  avatarUrl: claim.user!.avatarUrl,
                  count: 0, // noop
                  dateCredited: new Date().toISOString(), // noop
                  displayName: claim.user!.displayName,
                }}
              >
                {t('Expires {{date}}', { date: formatDate(claim.finishedAt!, 'l') })}
              </TooltipCreditRow>
            ))}
          </TooltipCreditsSection>
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
