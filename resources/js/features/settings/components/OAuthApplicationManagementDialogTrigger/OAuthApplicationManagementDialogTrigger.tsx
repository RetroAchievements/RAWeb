import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseAlertDialog,
  BaseAlertDialogAction,
  BaseAlertDialogCancel,
  BaseAlertDialogContent,
  BaseAlertDialogDescription,
  BaseAlertDialogFooter,
  BaseAlertDialogHeader,
  BaseAlertDialogTitle,
  BaseAlertDialogTrigger,
} from '@/common/components/+vendor/BaseAlertDialog';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useRotateOAuthApplicationSecretMutation } from '@/features/settings/hooks/mutations/useRotateOAuthApplicationSecretMutation';

import { OAuthCredentialField } from '../OAuthCredentialField';
import { OAuthApplicationForm } from './OAuthApplicationForm';

interface OAuthApplicationManagementDialogTriggerProps {
  application: App.Data.OAuthClient;
}

export const OAuthApplicationManagementDialogTrigger: FC<
  OAuthApplicationManagementDialogTriggerProps
> = ({ application }) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);
  const [rotatedSecret, setRotatedSecret] = useState<string | null>(null);

  const mutation = useRotateOAuthApplicationSecretMutation();

  const handleRotateClick = () => {
    toastMessage.promise(mutation.mutateAsync({ clientId: application.id }), {
      loading: t('Rotating secret...'),
      success: ({ data }) => {
        setRotatedSecret(data.secret);

        return t('Client secret rotated.');
      },
      error: t('Something went wrong.'),
    });
  };

  /**
   * A rotated secret is shown exactly once, so it must not be dismissed by accident.
   */
  const preventDismissWhileSecretIsVisible = (event: { preventDefault: () => void }) => {
    if (rotatedSecret) {
      event.preventDefault();
    }
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>
        <BaseButton size="sm" variant="link">
          {t('Manage')}
        </BaseButton>
      </BaseDialogTrigger>

      <BaseDialogContent
        shouldShowCloseButton={!rotatedSecret}
        onEscapeKeyDown={preventDismissWhileSecretIsVisible}
        onPointerDownOutside={preventDismissWhileSecretIsVisible}
      >
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Manage application')}</BaseDialogTitle>
          <BaseDialogDescription>
            {t('Update the application name and redirect URI.')}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <OAuthApplicationForm application={application} onUpdated={() => setIsOpen(false)} />

        {application.confidential ? (
          <div className="flex flex-col gap-3 border-t border-neutral-700 pt-4 light:border-neutral-200">
            <div>
              <h3 className="font-semibold">{t('Client secret')}</h3>
              <p className="text-sm text-neutral-400">
                {t('Rotating the secret immediately stops the old secret from working.')}
              </p>
            </div>

            {rotatedSecret ? (
              <div className="flex flex-col gap-3">
                <p className="text-sm font-medium text-amber-400">
                  {t("You won't be able to see the client secret again.")}
                </p>

                <OAuthCredentialField
                  credentialName={t('client secret')}
                  label={t('New secret')}
                  value={rotatedSecret}
                />

                <BaseButton className="self-end" onClick={() => setRotatedSecret(null)}>
                  {t("I've saved this secret")}
                </BaseButton>
              </div>
            ) : null}

            <BaseAlertDialog>
              <BaseAlertDialogTrigger asChild>
                <BaseButton className="self-start" variant="outline">
                  {t('Rotate secret')}
                </BaseButton>
              </BaseAlertDialogTrigger>

              <BaseAlertDialogContent>
                <BaseAlertDialogHeader>
                  <BaseAlertDialogTitle>{t('Rotate client secret?')}</BaseAlertDialogTitle>
                  <BaseAlertDialogDescription>
                    {t('The old secret will stop working immediately. This cannot be undone.')}
                  </BaseAlertDialogDescription>
                </BaseAlertDialogHeader>

                <BaseAlertDialogFooter>
                  <BaseAlertDialogCancel>{t('Cancel')}</BaseAlertDialogCancel>
                  <BaseAlertDialogAction onClick={handleRotateClick}>
                    {t('Rotate secret')}
                  </BaseAlertDialogAction>
                </BaseAlertDialogFooter>
              </BaseAlertDialogContent>
            </BaseAlertDialog>
          </div>
        ) : null}
      </BaseDialogContent>
    </BaseDialog>
  );
};
