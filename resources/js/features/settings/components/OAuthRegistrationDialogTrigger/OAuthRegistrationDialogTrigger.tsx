import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';

import { OAuthCredentialField } from '../OAuthCredentialField';
import { OAuthRegistrationForm } from './OAuthRegistrationForm';

interface OAuthRegistrationDialogTriggerProps {
  disabled?: boolean;
}

export const OAuthRegistrationDialogTrigger: FC<OAuthRegistrationDialogTriggerProps> = ({
  disabled,
}) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);

  /**
   * Credentials are held locally rather than read from page props. The client secret
   * is returned exactly once by the server and is never persisted anywhere the page
   * can read it back.
   */
  const [credentials, setCredentials] = useState<App.Data.OAuthClientCredentials | null>(null);

  let title = t('Register application');
  let description = t('Create an OAuth application for your integration.');

  if (credentials?.secret) {
    title = t('Save your credentials');
    description = t("You won't be able to see the client secret again.");
  } else if (credentials) {
    title = t('Your application is ready');
    description = t('Copy the client ID to finish configuring your application.');
  }

  const preventDismissWhileCredentialsAreVisible = (event: { preventDefault: () => void }) => {
    if (credentials) {
      event.preventDefault();
    }
  };

  const handleDoneClick = () => {
    setIsOpen(false);
    setCredentials(null);
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>
        <BaseButton disabled={disabled}>{t('Register application')}</BaseButton>
      </BaseDialogTrigger>

      <BaseDialogContent
        shouldShowCloseButton={!credentials}
        onEscapeKeyDown={preventDismissWhileCredentialsAreVisible}
        onPointerDownOutside={preventDismissWhileCredentialsAreVisible}
      >
        <BaseDialogHeader>
          <BaseDialogTitle>{title}</BaseDialogTitle>
          <BaseDialogDescription>{description}</BaseDialogDescription>
        </BaseDialogHeader>

        {credentials ? (
          <div className="flex flex-col gap-4">
            <OAuthCredentialField
              credentialName={t('client ID')}
              label={t('Client ID')}
              value={credentials.id}
            />

            {credentials.secret ? (
              <OAuthCredentialField
                credentialName={t('client secret')}
                label={t('Client secret')}
                value={credentials.secret}
              />
            ) : (
              <p className="text-sm text-neutral-400">
                {t('Public applications use PKCE and do not receive a client secret.')}
              </p>
            )}

            <BaseDialogFooter>
              <BaseButton onClick={handleDoneClick}>
                {credentials.secret ? t("I've saved these") : t('Done')}
              </BaseButton>
            </BaseDialogFooter>
          </div>
        ) : (
          <OAuthRegistrationForm onSuccess={setCredentials} />
        )}
      </BaseDialogContent>
    </BaseDialog>
  );
};
