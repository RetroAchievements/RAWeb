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
import { ClaimConfirmationDialog } from '@/features/games/components/ClaimConfirmationDialog';

export const ClaimActionButton: FC = () => {
  const { auth, backingGame, can, claimData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  if (!auth?.user || !can.createAchievementSetClaims) {
    return null;
  }

  if (claimData?.userClaim) {
    if (claimData.userClaim.isExtendable) {
      return (
        <ClaimConfirmationDialog
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
    if (!claimData.userClaim.isDroppable) {
      return (
        <BaseTooltip>
          <BaseTooltipTrigger asChild>
            <DisabledButton>{t('Drop Claim')}</DisabledButton>
          </BaseTooltipTrigger>
          <BaseTooltipContent>
            {t("You can't drop this claim while it's in review")}
          </BaseTooltipContent>
        </BaseTooltip>
      );
    }

    return (
      <ClaimConfirmationDialog
        action="drop"
        trigger={
          <BaseButton className="gap-1.5">
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
        <BaseTooltipTrigger asChild>
          <DisabledButton />
        </BaseTooltipTrigger>

        <BaseTooltipContent>{t("You've used all your achievement set claims.")}</BaseTooltipContent>
      </BaseTooltip>
    );
  }

  if (claimData.numUnresolvedTickets >= 2) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
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
        <BaseTooltipTrigger asChild>
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
}

const DisabledButton: FC<DisabledButtonProps> = ({ children }) => {
  const { t } = useTranslation();

  return (
    <span className={baseButtonVariants({ variant: 'defaultDisabled', className: 'gap-1.5' })}>
      <LuWrench />
      {children ?? t('Claim')}
    </span>
  );
};
