import type { FC } from 'react';
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
} from '@/common/components/+vendor/BaseDialog';
import type { TranslatedString } from '@/types/i18next';

interface TicketActionConfirmDialogProps {
  confirmLabel: TranslatedString;
  isOpen: boolean;
  onConfirm: () => void;
  onOpenChange: (isOpen: boolean) => void;
  title: TranslatedString;

  description?: TranslatedString;
}

export const TicketActionConfirmDialog: FC<TicketActionConfirmDialogProps> = ({
  confirmLabel,
  description,
  isOpen,
  onConfirm,
  onOpenChange,
  title,
}) => {
  const { t } = useTranslation();

  return (
    <BaseDialog open={isOpen} onOpenChange={onOpenChange}>
      <BaseDialogContent {...(description ? {} : { 'aria-describedby': undefined })}>
        <BaseDialogHeader>
          <BaseDialogTitle>{title}</BaseDialogTitle>
          {description ? <BaseDialogDescription>{description}</BaseDialogDescription> : null}
        </BaseDialogHeader>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseDialogClose asChild>
            <BaseButton size="sm" onClick={onConfirm}>
              {confirmLabel}
            </BaseButton>
          </BaseDialogClose>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
