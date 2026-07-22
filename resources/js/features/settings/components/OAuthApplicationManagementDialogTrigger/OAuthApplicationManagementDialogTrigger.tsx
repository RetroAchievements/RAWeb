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
import { useRegenerateOAuthApplicationSecretMutation } from '@/features/settings/hooks/mutations/useRegenerateOAuthApplicationSecretMutation';

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
  const [regeneratedSecret, setRegeneratedSecret] = useState<string | null>(null);

  const mutation = useRegenerateOAuthApplicationSecretMutation();

  const handleRegenerateClick = () => {
    toastMessage.promise(mutation.mutateAsync({ clientId: application.id }), {
      loading: t('Regenerating secret...'),
      success: ({ data }) => {
        setRegeneratedSecret(data.secret);

        return t('Client secret regenerated.');
      },
      error: t('Something went wrong.'),
    });
  };

  /**
   * A regenerated secret is shown exactly once, so it must not be dismissed by accident.
   */
  const preventDismissWhileSecretIsVisible = (event: { preventDefault: () => void }) => {
    if (regeneratedSecret) {
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
        shouldShowCloseButton={!regeneratedSecret}
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
                {t('Regenerating the secret immediately stops the old secret from working.')}
              </p>
            </div>

            {regeneratedSecret ? (
              <div className="flex flex-col gap-3">
                <p className="text-sm font-medium text-amber-400">
                  {t("You won't be able to see the client secret again.")}
                </p>

                <OAuthCredentialField
                  credentialName={t('client secret')}
                  label={t('New secret')}
                  value={regeneratedSecret}
                />

                <BaseButton className="self-end" onClick={() => setRegeneratedSecret(null)}>
                  {t("I've saved this secret")}
                </BaseButton>
              </div>
            ) : null}

            <BaseAlertDialog>
              <BaseAlertDialogTrigger asChild>
                <BaseButton className="self-start" variant="outline">
                  {t('Regenerate secret')}
                </BaseButton>
              </BaseAlertDialogTrigger>

              <BaseAlertDialogContent>
                <BaseAlertDialogHeader>
                  <BaseAlertDialogTitle>{t('Regenerate client secret?')}</BaseAlertDialogTitle>
                  <BaseAlertDialogDescription>
                    {t('The old secret will stop working immediately. This cannot be undone.')}
                  </BaseAlertDialogDescription>
                </BaseAlertDialogHeader>

                <BaseAlertDialogFooter>
                  <BaseAlertDialogCancel>{t('Cancel')}</BaseAlertDialogCancel>
                  <BaseAlertDialogAction onClick={handleRegenerateClick}>
                    {t('Regenerate secret')}
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
