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
import { DialogCheckboxConfirmation } from '@/common/components/DialogCheckboxConfirmation';
import { usePageProps } from '@/common/hooks/usePageProps';
import { ClaimDialogDescriptions } from '@/features/games/components/ClaimDialogDescriptions';
import { type ClaimActionType, useClaimActions } from '@/features/games/hooks/useClaimActions';
import { useClaimDialogState } from '@/features/games/hooks/useClaimDialogState';

interface ClaimConfirmationDialogProps {
  action: ClaimActionType;
  trigger: ReactNode;
}

export const ClaimConfirmationDialog: FC<ClaimConfirmationDialogProps> = ({ action, trigger }) => {
  const { backingGame, claimData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);
  const [isAcknowledged, setIsAcknowledged] = useState(false);

  const { executeCreateClaim, executeDropClaim, executeExtendClaim, executeCompleteClaim } =
    useClaimActions();
  const { createClaimDialogVariant, hasDialogNotice, requiresTicketAcknowledgment } =
    useClaimDialogState(action);

  const handleOpenChange = (open: boolean) => {
    setIsOpen(open);

    if (!open) {
      setIsAcknowledged(false);
    }
  };

  const handleConfirmClick = async () => {
    handleOpenChange(false);

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

  const getDialogTitle = () => {
    if (action === 'create' && hasDialogNotice) {
      switch (createClaimDialogVariant) {
        case 'newSet':
          return t('Create primary claim?');

        case 'revision':
          return t('Create revision claim?');

        case 'collaboration':
          return t('Create collaboration claim?');
      }
    }

    if (action === 'extend') {
      return t('Extend claim?');
    }

    if (action === 'complete' && hasDialogNotice) {
      return t('Complete claim?');
    }

    return t('Are you sure?');
  };

  const getConfirmButtonText = () => {
    switch (action) {
      case 'create':
        switch (createClaimDialogVariant) {
          case 'newSet':
            return t('Create primary claim');

          case 'revision':
            return t('Create revision claim');

          case 'collaboration':
            return t('Create collaboration claim');
        }

      case 'drop':
        return t('Drop claim');

      case 'extend':
        return t('Extend claim');

      case 'complete':
        return t('Complete claim');

      default:
        return '';
    }
  };

  const getConfirmButtonVariant = () => {
    if (action === 'drop') {
      return 'destructive' as const;
    }

    return 'default' as const;
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={handleOpenChange}>
      <BaseDialogTrigger asChild>{trigger}</BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{getDialogTitle()}</BaseDialogTitle>
          <BaseDialogDescription asChild>
            <div className="text-left text-neutral-300 light:text-neutral-700">
              <ClaimDialogDescriptions action={action} />
            </div>
          </BaseDialogDescription>
        </BaseDialogHeader>

        {requiresTicketAcknowledgment ? (
          <DialogCheckboxConfirmation checked={isAcknowledged} onCheckedChange={setIsAcknowledged}>
            {t('I understand and want to continue.')}
          </DialogCheckboxConfirmation>
        ) : null}

        <BaseDialogFooter className="mt-2">
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            className="min-w-40"
            onClick={handleConfirmClick}
            disabled={requiresTicketAcknowledgment && !isAcknowledged}
            variant={getConfirmButtonVariant()}
            size="sm"
          >
            {getConfirmButtonText()}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
