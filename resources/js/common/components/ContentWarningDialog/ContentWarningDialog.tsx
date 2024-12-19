import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import {
  BaseAlertDialog,
  BaseAlertDialogAction,
  BaseAlertDialogContent,
  BaseAlertDialogDescription,
  BaseAlertDialogFooter,
  BaseAlertDialogHeader,
  BaseAlertDialogTitle,
} from '../+vendor/BaseAlertDialog';
import { useSuppressContentWarningMutation } from './useSuppressContentWarningMutation';

interface ContentWarningDialogProps {
  /** On clicking "No", the user will be redirected to this URL. Defaults to the home page. */
  noHref?: string;
}

export const ContentWarningDialog: FC<ContentWarningDialogProps> = ({ noHref = route('home') }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(
    auth?.user.preferences?.shouldAlwaysBypassContentWarnings ? false : true,
  );

  const mutation = useSuppressContentWarningMutation();

  const handlePermanentYesClick = () => {
    mutation.mutate(); // Fire and forget.
    setIsOpen(false);
  };

  const handleYesClick = () => {
    setIsOpen(false);
  };

  const handleNoClick = () => {
    window.location.assign(noHref);
  };

  return (
    <>
      {/* This prevents a flash of the page content appearing before the dialog has rendered on the client. */}
      {isOpen ? (
        <div className="absolute left-0 top-0 z-20 h-full w-full bg-black/10 backdrop-blur-sm" />
      ) : null}

      <BaseAlertDialog open={isOpen} onOpenChange={setIsOpen}>
        <BaseAlertDialogContent
          shouldBlurBackdrop={true}
          onEscapeKeyDown={(event) => {
            event.preventDefault(); // don't allow closing with the escape key
          }}
        >
          <BaseAlertDialogHeader>
            <BaseAlertDialogTitle>{t('Content Warning')}</BaseAlertDialogTitle>

            <BaseAlertDialogDescription className="flex flex-col gap-4">
              <span>
                {t('This page may contain content that is not appropriate for all ages.')}
              </span>
              <span>{t('Are you sure you want to view this page?')}</span>
            </BaseAlertDialogDescription>
          </BaseAlertDialogHeader>

          <BaseAlertDialogFooter className="mt-2 flex flex-col gap-5 sm:flex-row sm:gap-1">
            <BaseAlertDialogAction onClick={handlePermanentYesClick}>
              {t("Yes, and don't ask again")}
            </BaseAlertDialogAction>

            <BaseAlertDialogAction onClick={handleYesClick}>{t('Yes')}</BaseAlertDialogAction>

            <BaseAlertDialogAction onClick={handleNoClick}>{t('No')}</BaseAlertDialogAction>
          </BaseAlertDialogFooter>
        </BaseAlertDialogContent>
      </BaseAlertDialog>
    </>
  );
};
