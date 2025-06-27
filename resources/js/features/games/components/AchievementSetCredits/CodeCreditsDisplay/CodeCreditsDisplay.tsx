import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCode } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { cn } from '@/common/utils/cn';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface CodeCreditsDisplayProps {
  authorCredits: App.Platform.Data.UserCredits[];
  logicCredits: App.Platform.Data.UserCredits[];
  maintainerCredits: App.Platform.Data.UserCredits[];
}

export const CodeCreditsDisplay: FC<CodeCreditsDisplayProps> = ({
  authorCredits,
  logicCredits,
  maintainerCredits,
}) => {
  // Dedupe logic credits with authors - it's a bit redundant.
  // TODO do this on the server to reduce initial props size
  const filteredLogicCredits = logicCredits.filter(
    (logicUser) => !authorCredits.some((author) => author.displayName === logicUser.displayName),
  );

  const codeCreditUsers = [...maintainerCredits, ...filteredLogicCredits].filter(
    (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
  );

  if (filteredLogicCredits.length === 0 && codeCreditUsers.length === 0) {
    return null;
  }

  return (
    <div
      className={cn(
        'flex items-center rounded-md bg-neutral-800/70 py-1 pr-2 text-neutral-400',
        'light:border light:border-neutral-200 light:bg-white light:text-neutral-600',
      )}
    >
      <CodeCreditIcon activeMaintainers={maintainerCredits} logicCredits={filteredLogicCredits} />

      <UserAvatarStack
        users={codeCreditUsers}
        maxVisible={6}
        size={24}
        isOverlappingAvatars={false}
      />
    </div>
  );
};

interface CodeCreditIconProps {
  activeMaintainers: App.Platform.Data.UserCredits[];
  logicCredits: App.Platform.Data.UserCredits[];
}

const CodeCreditIcon: FC<CodeCreditIconProps> = ({ activeMaintainers, logicCredits }) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="px-2 py-[5px]">
        <LuCode className="size-3.5" />
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <div className="flex flex-col gap-3">
          {activeMaintainers.length ? (
            <TooltipCreditsSection headingLabel={t('Achievement Maintainers')}>
              {activeMaintainers.map((credit) => (
                <TooltipCreditRow
                  key={`maintainer-credit-${credit.displayName}`}
                  credit={credit}
                  showCreditDate={true}
                />
              ))}
            </TooltipCreditsSection>
          ) : null}

          {logicCredits.length ? (
            <TooltipCreditsSection headingLabel={t('Code Contributors')}>
              {logicCredits.map((credit) => (
                <TooltipCreditRow
                  key={`maintainer-credit-${credit.displayName}`}
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
