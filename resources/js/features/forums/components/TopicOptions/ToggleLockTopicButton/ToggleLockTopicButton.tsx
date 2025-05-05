import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLock, LuLockOpen } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

export const ToggleLockTopicButton: FC = () => {
  const { forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const isLocked = !!forumTopic.lockedAt;
  const Icon = isLocked ? LuLockOpen : LuLock;

  const mutation = useMutation({
    mutationFn: () => axios.post(route('api.forum-topic.toggle-lock', { topic: forumTopic.id })),

    onSuccess: () => {
      router.reload();
    },
  });

  const handleClick = () => {
    const confirmMessage = isLocked
      ? t('Are you sure you want to unlock this topic?')
      : t('Are you sure you want to lock this topic?');

    const loadingMessage = isLocked ? t('Unlocking...') : t('Locking...');
    const successMessage = isLocked ? t('Unlocked!') : t('Locked!');

    if (!confirm(confirmMessage)) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: loadingMessage,
      success: successMessage,
      error: 'Something went wrong.',
    });
  };

  return (
    <BaseButton size="sm" onClick={handleClick}>
      <Icon className="mr-1.5 size-4" />
      {isLocked ? t('Unlock') : t('Lock')}
    </BaseButton>
  );
};
