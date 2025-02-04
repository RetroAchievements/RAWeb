import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { ForumBreadcrumbs } from '@/common/components/ForumBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';

import { ForumPostCard } from '../ForumPostCard';
import { CreateTopicForm } from './CreateTopicForm';

export const CreateForumTopicMainRoot: FC = memo(() => {
  const { forum } = usePageProps<App.Data.CreateForumTopicPageProps>();

  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  return (
    <div className="flex flex-col gap-4">
      <ForumBreadcrumbs
        forum={forum}
        forumCategory={forum.category}
        t_currentPageLabel={t('Start new topic')}
      />

      <CreateTopicForm onPreview={initiatePreview} />

      {previewContent ? (
        <div data-testid="preview-content">
          <ForumPostCard body={previewContent} />
        </div>
      ) : null}
    </div>
  );
});
