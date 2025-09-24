import { type FC, type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '../+vendor/BaseDialog';
import { BetaFeedbackForm } from './BetaFeedbackForm';

interface BetaFeedbackDialogProps {
  betaName: string;
  children: ReactNode;
}

export const BetaFeedbackDialog: FC<BetaFeedbackDialogProps> = ({ betaName, children }) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>{children}</BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader className="mb-4">
          <BaseDialogTitle>{t('Share Your Thoughts')}</BaseDialogTitle>
          <BaseDialogDescription>
            {t(
              "Your feedback will help us improve the page before it becomes generally available for everyone. You can submit this form as many times as you'd like if your opinion changes.",
            )}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BetaFeedbackForm betaName={betaName} onSubmitSuccess={() => setIsOpen(false)} />
      </BaseDialogContent>
    </BaseDialog>
  );
};
