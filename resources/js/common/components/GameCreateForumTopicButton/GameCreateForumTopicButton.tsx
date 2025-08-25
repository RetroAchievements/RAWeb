import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { useCreateOfficialForumTopicMutation } from '@/common/hooks/mutations/useCreateOfficialForumTopicMutation';
import { usePageProps } from '@/common/hooks/usePageProps';

import { BaseButton } from '../+vendor/BaseButton';
import { toastMessage } from '../+vendor/BaseToaster';

export const GameCreateForumTopicButton: FC = () => {
  const { backingGame, can } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const mutation = useCreateOfficialForumTopicMutation();

  if (!can?.createGameForumTopic || backingGame?.forumTopicId) {
    return null;
  }

  const handleClick = async () => {
    if (!confirm(t('Are you sure you want to create the official forum topic for this page?'))) {
      return false;
    }

    await toastMessage.promise(mutation.mutateAsync({ gameId: backingGame.id }), {
      loading: t('Creating...'),
      success: t('Created!'),
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseButton onClick={handleClick} className="items-center !justify-start gap-2">
      <LuWrench className="size-4" />
      {t('Create New Forum Topic')}
    </BaseButton>
  );
};
