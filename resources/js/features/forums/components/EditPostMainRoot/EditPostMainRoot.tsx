import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { ForumBreadcrumbs } from '@/common/components/ForumBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';

import { ForumPostCard } from '../ForumPostCard';
import { EditPostForm } from './EditPostForm';

export const EditPostMainRoot: FC = memo(() => {
  const { forumTopicComment } = usePageProps<App.Data.EditForumTopicCommentPageProps>();

  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  return (
    <div>
      <ForumBreadcrumbs
        forum={forumTopicComment.forumTopic!.forum}
        forumCategory={forumTopicComment.forumTopic!.forum!.category}
        forumTopic={forumTopicComment.forumTopic}
        t_currentPageLabel={t('Edit Post')}
      />

      <EditPostForm onPreview={initiatePreview} />

      {previewContent ? (
        <div data-testid="preview-content" className="mt-4">
          <ForumPostCard body={previewContent} />
        </div>
      ) : null}
    </div>
  );
});
