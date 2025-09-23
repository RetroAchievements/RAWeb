import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { useSuppressMatureContentWarningMutation } from '@/common/hooks/mutations/useSuppressMatureContentWarningMutation';
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
import { BaseButton } from '../+vendor/BaseButton';

interface MatureContentWarningDialogProps {
  /** On clicking "No", the user will be redirected to this URL. Defaults to the home page. */
  noHref?: string;
}

export const MatureContentWarningDialog: FC<MatureContentWarningDialogProps> = ({
  noHref = route('home'),
}) => {
  const { auth, ziggy } = usePageProps();
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(() => {
    // Check if the user has the permanent preference to bypass warnings.
    if (auth?.user.preferences?.shouldAlwaysBypassContentWarnings) {
      return false;
    }

    // Check if the URL has the "mature_content_accepted" parameter.
    // Use `ziggy.query`, otherwise this will throw a hydration error.
    if (ziggy?.query['mature_content_accepted'] === '1') {
      return false;
    }

    return true;
  });

  const mutation = useSuppressMatureContentWarningMutation();

  const handlePermanentYesClick = () => {
    mutation.mutate(); // Fire and forget.
    setIsOpen(false);
  };

  const handleYesClick = () => {
    // Add the "mature_content_accepted" parameter to the current URL.
    const url = new URL(window.location.href);
    url.searchParams.set('mature_content_accepted', '1');
    window.history.replaceState({}, '', url.href);

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
          className="max-w-xl"
          shouldBlurBackdrop={true}
          onEscapeKeyDown={(event) => {
            event.preventDefault(); // don't allow closing with the escape key
          }}
        >
          <BaseAlertDialogHeader>
            <BaseAlertDialogTitle>{t('Mature Content Warning')}</BaseAlertDialogTitle>

            <BaseAlertDialogDescription className="flex flex-col gap-4">
              <span>{t('This content is intended for mature audiences.')}</span>
              <span>{t('Do you want to continue?')}</span>
            </BaseAlertDialogDescription>
          </BaseAlertDialogHeader>

          <BaseAlertDialogFooter className="mt-2 flex flex-col gap-4 sm:w-full sm:items-center sm:justify-between sm:gap-0 lg:flex-row">
            <BaseAlertDialogAction
              onClick={handlePermanentYesClick}
              className="w-full border-none p-2 sm:-ml-2 sm:w-auto"
            >
              {t('Always allow mature content')}
            </BaseAlertDialogAction>

            <div className="flex justify-center gap-2">
              <BaseButton onClick={handleNoClick}>{t('No')}</BaseButton>

              <BaseAlertDialogAction onClick={handleYesClick}>
                {t("Yes, I'm an adult")}
              </BaseAlertDialogAction>
            </div>
          </BaseAlertDialogFooter>
        </BaseAlertDialogContent>
      </BaseAlertDialog>
    </>
  );
};
