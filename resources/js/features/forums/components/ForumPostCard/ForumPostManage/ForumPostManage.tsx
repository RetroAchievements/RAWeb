import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateUserForumPermissionsMutation } from '@/features/forums/hooks/useUpdateUserForumPermissionsMutation';

interface ForumPostManageProps {
  comment: App.Data.ForumTopicComment;
}

export const ForumPostManage: FC<ForumPostManageProps> = ({ comment }) => {
  const { t } = useTranslation();

  const mutation = useUpdateUserForumPermissionsMutation();

  const handleAuthorizeClick = () => {
    if (!confirm(t('Are you sure you want to authorize forum posting privileges for this user?'))) {
      return;
    }

    toastMessage.promise(
      mutation.mutateAsync({ displayName: comment.user!.displayName, isAuthorized: true }),
      {
        loading: t('Authorizing...'),
        success: t('Authorized!'),
        error: t('Something went wrong.'),
      },
    );
  };

  const handleBlockClick = () => {
    if (
      !confirm(
        t(
          'Are you sure you want to permanently block this user from posting on the forum and flag their account as a spammer?',
        ),
      )
    ) {
      return;
    }

    toastMessage.promise(
      mutation.mutateAsync({ displayName: comment.user!.displayName, isAuthorized: false }),
      {
        loading: t('Blocking...'),
        success: t('Blocked!'),
        error: t('Something went wrong.'),
      },
    );
  };

  return (
    <>
      <BaseButton
        size="sm"
        className="max-h-[22px] !p-1 !text-2xs lg:!text-xs"
        onClick={handleAuthorizeClick}
      >
        {t('Authorize')}
      </BaseButton>

      <BaseButton
        size="sm"
        variant="destructive"
        className="max-h-[22px] !p-1 !text-2xs lg:!text-xs"
        onClick={handleBlockClick}
      >
        {t('Block')}
      </BaseButton>
    </>
  );
};
