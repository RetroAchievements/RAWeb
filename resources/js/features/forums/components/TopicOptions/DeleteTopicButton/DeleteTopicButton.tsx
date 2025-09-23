import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuTrash } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useDeleteForumTopicMutation } from '@/features/forums/hooks/mutations/useDeleteForumTopicMutation';

export const DeleteTopicButton: FC = () => {
  const { forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();
  const { t } = useTranslation();

  const mutation = useDeleteForumTopicMutation();

  const handleClick = () => {
    if (!confirm(t('Are you sure you want to permanently delete this topic?'))) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync({ topic: forumTopic.id }), {
      loading: 'Deleting...',
      success: () => {
        window.location.assign(`/viewforum.php?f=${forumTopic.forum!.id}`);

        return 'Deleted!';
      },
      error: 'Something went wrong.',
    });
  };

  return (
    <BaseButton variant="destructive" size="sm" onClick={handleClick}>
      <LuTrash className="mr-1.5 size-4" />
      {t('Delete Permanently')}
    </BaseButton>
  );
};
