import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLightbulb } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { cn } from '@/common/utils/cn';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface DesignCreditsDisplayProps {
  designCredits: App.Platform.Data.UserCredits[];
  testingCredits: App.Platform.Data.UserCredits[];
  writingCredits: App.Platform.Data.UserCredits[];
}

export const DesignCreditsDisplay: FC<DesignCreditsDisplayProps> = ({
  designCredits,
  testingCredits,
  writingCredits,
}) => {
  return (
    <div
      className={cn(
        'flex items-center gap-2 rounded-md bg-neutral-800/70 px-2 py-1 text-neutral-400',
        'light:border light:border-neutral-200 light:bg-white light:text-neutral-600',
      )}
    >
      <DesignCreditIcon
        designCredits={designCredits}
        testingCredits={testingCredits}
        writingCredits={writingCredits}
      />

      <UserAvatarStack
        users={designCredits}
        maxVisible={8}
        size={24}
        isOverlappingAvatars={false}
      />
    </div>
  );
};

interface DesignCreditIconProps {
  designCredits: App.Platform.Data.UserCredits[];
  testingCredits: App.Platform.Data.UserCredits[];
  writingCredits: App.Platform.Data.UserCredits[];
}

const DesignCreditIcon: FC<DesignCreditIconProps> = ({
  designCredits,
  testingCredits,
  writingCredits,
}) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <LuLightbulb className="size-3.5" />
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <div className="flex flex-col gap-3">
          {designCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Achievement Design/Ideas')}>
              {designCredits.map((credit) => (
                <TooltipCreditRow
                  key={`design-credit-${credit.displayName}`}
                  credit={credit}
                  showAchievementCount={true}
                />
              ))}
            </TooltipCreditsSection>
          ) : null}

          {testingCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Playtesters')}>
              {testingCredits.map((credit) => (
                /**
                 * TODO show dates
                 * right now these are attached to achievements... it should probably be set credit
                 */
                <TooltipCreditRow key={`testing-credit-${credit.displayName}`} credit={credit} />
              ))}
            </TooltipCreditsSection>
          ) : null}

          {writingCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Writing Contributions')}>
              {writingCredits.map((credit) => (
                <TooltipCreditRow
                  key={`writing-credit-${credit.displayName}`}
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
