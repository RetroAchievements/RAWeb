import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuPalette } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { cn } from '@/common/utils/cn';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface ArtworkCreditsDisplayProps {
  achievementArtworkCredits: App.Platform.Data.UserCredits[];
  badgeArtworkCredits: App.Platform.Data.UserCredits[];
}

export const ArtworkCreditsDisplay: FC<ArtworkCreditsDisplayProps> = ({
  achievementArtworkCredits,
  badgeArtworkCredits,
}) => {
  return (
    <div
      className={cn(
        'flex items-center rounded-md bg-neutral-800/70 py-1 pr-2 text-neutral-400',
        'light:border light:border-neutral-200 light:bg-white light:text-neutral-600',
      )}
    >
      <ArtCreditIcon
        achievementArtworkCredits={achievementArtworkCredits}
        badgeArtworkCredits={badgeArtworkCredits}
      />

      <UserAvatarStack
        users={achievementArtworkCredits}
        maxVisible={5}
        size={20}
        isOverlappingAvatars={false}
      />
    </div>
  );
};

interface ArtCreditIconProps {
  achievementArtworkCredits: App.Platform.Data.UserCredits[];
  badgeArtworkCredits: App.Platform.Data.UserCredits[];
}

const ArtCreditIcon: FC<ArtCreditIconProps> = ({
  achievementArtworkCredits,
  badgeArtworkCredits,
}) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="px-2 py-[5px]">
        <LuPalette className="size-3.5" />
      </BaseTooltipTrigger>

      <BaseTooltipContent className="w-[14rem] whitespace-nowrap">
        <div className="flex flex-col gap-3">
          {badgeArtworkCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Game Badge Artwork')}>
              {badgeArtworkCredits.map((credit) => (
                <TooltipCreditRow
                  key={`tooltip-badge-artwork-credit-${credit.displayName}`}
                  credit={credit}
                  showCreditDate={true}
                />
              ))}
            </TooltipCreditsSection>
          ) : null}

          {achievementArtworkCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Achievement Artwork')}>
              {achievementArtworkCredits.map((credit) => (
                <TooltipCreditRow
                  key={`tooltip-ach-artwork-credit-${credit.displayName}`}
                  credit={credit}
                  showAchievementCount={true}
                />
              ))}
            </TooltipCreditsSection>
          ) : null}
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
