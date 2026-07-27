import { type FC, useLayoutEffect, useState } from 'react';
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
import { DialogCheckboxConfirmation } from '@/common/components/DialogCheckboxConfirmation';

interface ChangeUsernameConfirmDialogProps {
  isOpen: boolean;
  isSubmitting: boolean;
  onConfirm: () => void;
  onOpenChange: (isOpen: boolean) => void;
  requestedUsername: string;
}

export const ChangeUsernameConfirmDialog: FC<ChangeUsernameConfirmDialogProps> = ({
  isOpen,
  isSubmitting,
  onConfirm,
  onOpenChange,
  requestedUsername,
}) => {
  const { t } = useTranslation();

  const [isChecked, setIsChecked] = useState(false);

  useLayoutEffect(() => {
    setIsChecked(false);
  }, [isOpen]);

  return (
    <BaseDialog open={isOpen} onOpenChange={onOpenChange}>
      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle className="pb-3">{t('Is this right?')}</BaseDialogTitle>

          <p
            data-sentry-mask
            className="py-1 text-center font-mono text-lg font-bold break-all text-neutral-100 light:text-neutral-900"
          >
            {requestedUsername}
          </p>

          <BaseDialogDescription className="flex flex-col gap-2 pt-2 text-left">
            <span>{t('This becomes your public name everywhere.')}</span>

            <span>
              {t("You can't ask again for 30 days, even if this request is turned down.")}
            </span>
          </BaseDialogDescription>
        </BaseDialogHeader>

        <DialogCheckboxConfirmation checked={isChecked} onCheckedChange={setIsChecked}>
          {t('Yes, this is the name I want.')}
        </DialogCheckboxConfirmation>

        <BaseDialogFooter className="mt-4">
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            type="button"
            size="sm"
            onClick={onConfirm}
            disabled={!isChecked || isSubmitting}
          >
            {t('Change Name')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
