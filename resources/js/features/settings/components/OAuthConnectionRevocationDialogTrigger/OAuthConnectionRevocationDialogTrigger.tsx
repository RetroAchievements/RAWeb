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
import { useRevokeOAuthConnectionMutation } from '@/features/settings/hooks/mutations/useRevokeOAuthConnectionMutation';

interface OAuthConnectionRevocationDialogTriggerProps {
  application: App.Data.ConnectedOAuthApplication;
}

export const OAuthConnectionRevocationDialogTrigger: FC<
  OAuthConnectionRevocationDialogTriggerProps
> = ({ application }) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);

  const mutation = useRevokeOAuthConnectionMutation();

  const handleRevokeClick = () => {
    toastMessage.promise(mutation.mutateAsync({ clientId: application.clientId }), {
      loading: t('Revoking application...'),
      success: () => {
        setIsOpen(false);

        return t('Application revoked.');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={setIsOpen}>
      <BaseDialogTrigger asChild>
        <BaseButton size="sm" variant="destructive">
          {t('Revoke')}
        </BaseButton>
      </BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>
            {t('Revoke {{applicationName}}?', {
              applicationName: application.name,
            })}
          </BaseDialogTitle>
          <BaseDialogDescription>
            {t('This application will immediately lose access to your RetroAchievements account.')}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link">{t('Cancel')}</BaseButton>
          </BaseDialogClose>

          <BaseButton
            disabled={mutation.isPending}
            variant="destructive"
            onClick={handleRevokeClick}
          >
            {t('Revoke application')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
