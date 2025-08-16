import { type FC, type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { usePageProps } from '@/common/hooks/usePageProps';
import { ClaimDialogDescriptions } from '@/features/games/components/ClaimDialogDescriptions';
import { type ClaimActionType, useClaimActions } from '@/features/games/hooks/useClaimActions';

interface ClaimConfirmationDialogProps {
  action: ClaimActionType;
  trigger: ReactNode;
}

export const ClaimConfirmationDialog: FC<ClaimConfirmationDialogProps> = ({ action, trigger }) => {
  const { backingGame, claimData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);

  const { executeCreateClaim, executeDropClaim, executeExtendClaim, executeCompleteClaim } =
    useClaimActions();

  const handleConfirmClick = async () => {
    setIsOpen(false);

    switch (action) {
      case 'create':
        await executeCreateClaim(backingGame.id);
        break;

      case 'drop':
        await executeDropClaim(backingGame.id);
        break;

      case 'extend':
        await executeExtendClaim(backingGame.id);
        break;

      case 'complete':
        if (claimData?.userClaim?.id) {
          await executeCompleteClaim(claimData.userClaim.id);
        }
        break;
    }
  };

  const getConfirmButtonText = () => {
    switch (action) {
      case 'create':
        return t('Yes, create the claim');

      case 'drop':
        return t('Yes, drop the claim');

      case 'extend':
        return t('Yes, extend the claim');

      case 'complete':
        return t('Yes, complete the claim');

      default:
        return '';
    }
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>{trigger}</BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Are you sure?')}</BaseDialogTitle>
          <BaseDialogDescription>
            <ClaimDialogDescriptions action={action} />
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link">{t('Nevermind')}</BaseButton>
          </BaseDialogClose>

          <BaseButton onClick={handleConfirmClick}>{getConfirmButtonText()}</BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
