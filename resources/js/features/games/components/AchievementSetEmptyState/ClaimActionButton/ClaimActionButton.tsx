import { type FC, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { ClaimConfirmationDialog } from '@/features/games/components/ClaimConfirmationDialog';

export const ClaimActionButton: FC = () => {
  const { auth, backingGame, claimData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const hasClaimRole =
    auth?.user.roles.includes('developer-junior') || auth?.user.roles.includes('developer');

  if (!auth?.user || !hasClaimRole) {
    return null;
  }

  if (claimData?.userClaim) {
    if (claimData.userClaim.isExtendable) {
      return (
        <ClaimConfirmationDialog
          data-testid="claim-button"
          action="extend"
          trigger={
            <BaseButton className="gap-1.5">
              <LuWrench />
              {t('Extend Claim')}
            </BaseButton>
          }
        />
      );
    }

    // Check if the claim can be dropped (not in review).
    // Only show on XS breakpoint since the sidebar has drop claim for larger screens.
    if (!claimData.userClaim.isDroppable) {
      return (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <DisabledButton className="sm:hidden">{t('Drop Claim')}</DisabledButton>
          </BaseTooltipTrigger>
          <BaseTooltipContent>
            {t("You can't drop this claim while it's in review")}
          </BaseTooltipContent>
        </BaseTooltip>
      );
    }

    return (
      <ClaimConfirmationDialog
        data-testid="claim-button"
        action="drop"
        trigger={
          <BaseButton className="gap-1.5 sm:hidden">
            <LuWrench />
            {t('Drop Claim')}
          </BaseButton>
        }
      />
    );
  }

  if (!claimData?.numClaimsRemaining && !claimData?.isSoleAuthor) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger>
          <DisabledButton />
        </BaseTooltipTrigger>

        <BaseTooltipContent>{t("You've used all your achievement set claims.")}</BaseTooltipContent>
      </BaseTooltip>
    );
  }

  if (claimData.numUnresolvedTickets >= 2) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger>
          <DisabledButton />
        </BaseTooltipTrigger>

        <BaseTooltipContent>
          {t('You need to resolve your tickets before making any new claims.')}
        </BaseTooltipContent>
      </BaseTooltip>
    );
  }

  if (auth.user.roles.includes('developer-junior') && !backingGame.forumTopicId) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger>
          <DisabledButton />
        </BaseTooltipTrigger>

        <BaseTooltipContent>
          {t('Please ask a Code Reviewer to create a forum topic for the game first.')}
        </BaseTooltipContent>
      </BaseTooltip>
    );
  }

  return (
    <ClaimConfirmationDialog
      data-testid="claim-button"
      action="create"
      trigger={
        <BaseButton className="gap-1.5">
          <LuWrench />
          {t('Claim')}
        </BaseButton>
      }
    />
  );
};

interface DisabledButtonProps {
  children?: ReactNode;
  className?: string;
}

const DisabledButton: FC<DisabledButtonProps> = ({ children, className }) => {
  const { t } = useTranslation();

  return (
    <span
      role="button"
      aria-disabled={true}
      className={baseButtonVariants({
        variant: 'defaultDisabled',
        className: cn('cursor-not-allowed gap-1.5', className),
      })}
    >
      <LuWrench />
      {children ?? t('Claim')}
    </span>
  );
};
