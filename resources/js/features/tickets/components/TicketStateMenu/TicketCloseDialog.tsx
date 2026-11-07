import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';

import { TicketCloseForm } from './TicketCloseForm';

interface TicketCloseDialogProps {
  isOpen: boolean;
  onOpenChange: (isOpen: boolean) => void;
}

export const TicketCloseDialog: FC<TicketCloseDialogProps> = ({ isOpen, onOpenChange }) => {
  const { t } = useTranslation();

  const handleSubmitSuccess = () => {
    onOpenChange(false);
    router.reload();
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={onOpenChange}>
      <BaseDialogContent
        aria-describedby={undefined}
        className="max-h-[calc(100dvh-2rem)] overflow-y-auto max-sm:top-4 max-sm:translate-y-0 sm:max-w-3xl"
        onOpenAutoFocus={(event) => event.preventDefault()}
      >
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Close ticket')}</BaseDialogTitle>
        </BaseDialogHeader>

        <TicketCloseForm onSubmitSuccess={handleSubmitSuccess} />
      </BaseDialogContent>
    </BaseDialog>
  );
};
