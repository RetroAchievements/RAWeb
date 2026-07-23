import { type FC, useState } from 'react';
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
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useDeactivateOAuthApplicationMutation } from '@/features/settings/hooks/mutations/useDeactivateOAuthApplicationMutation';

interface OAuthApplicationDeactivationDialogTriggerProps {
  application: App.Data.OAuthClient;
}

export const OAuthApplicationDeactivationDialogTrigger: FC<
  OAuthApplicationDeactivationDialogTriggerProps
> = ({ application }) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);

  const mutation = useDeactivateOAuthApplicationMutation();

  const handleDeactivateClick = () => {
    toastMessage.promise(mutation.mutateAsync({ clientId: application.id }), {
      loading: t('Deactivating application...'),
      success: () => {
        setIsOpen(false);

        return t('Application deactivated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>
        <BaseButton size="sm" variant="destructive">
          {t('Deactivate')}
        </BaseButton>
      </BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>
            {t('Deactivate {{applicationName}}?', {
              applicationName: application.name,
            })}
          </BaseDialogTitle>
          <BaseDialogDescription>
            {t(
              'Every access and refresh token issued to this application will be revoked. This cannot be undone.',
            )}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link">{t('Cancel')}</BaseButton>
          </BaseDialogClose>

          <BaseButton
            disabled={mutation.isPending}
            variant="destructive"
            onClick={handleDeactivateClick}
          >
            {t('Deactivate application')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
