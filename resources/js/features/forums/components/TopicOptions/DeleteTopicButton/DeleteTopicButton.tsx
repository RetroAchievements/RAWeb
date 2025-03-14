import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

export const DeleteTopicButton: FC = () => {
  const { forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const mutation = useMutation({
    mutationFn: () => axios.delete(route('api.forum-topic.destroy', { topic: forumTopic.id })),

    onSuccess: () => {
      window.location.assign(`/viewforum.php?f=${forumTopic.forum!.id}`);
    },
  });

  const handleClick = () => {
    if (!confirm(t('Are you sure you want to permanently delete this topic?'))) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: 'Deleting...',
      success: 'Deleted!',
      error: 'Something went wrong.',
    });
  };

  return (
    <BaseButton variant="destructive" size="sm" onClick={handleClick}>
      {t('Delete Permanently')}
    </BaseButton>
  );
};
