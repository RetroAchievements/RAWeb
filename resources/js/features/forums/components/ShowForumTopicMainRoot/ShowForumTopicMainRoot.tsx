import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLock } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { ForumBreadcrumbs } from '@/common/components/ForumBreadcrumbs';
import { FullPaginator } from '@/common/components/FullPaginator';
import { MutedMessage } from '@/common/components/MutedMessage';
import { SignInMessage } from '@/common/components/SignInMessage';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { ForumPostCard } from '../ForumPostCard';
import { QuickReplyForm } from '../QuickReplyForm';
import { TopicOptions } from '../TopicOptions';

export const ShowForumTopicMainRoot: FC = () => {
  const {
    accessibleTeamAccounts,
    auth,
    can,
    forumTopic,
    isSubscribed,
    paginatedForumTopicComments,
    ziggy,
  } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('forum-topic.show', {
        topic: forumTopic.id,
        _query: { page: newPageValue },
      }),
    );
  };

  return (
    <div>
      <ForumBreadcrumbs
        forum={forumTopic.forum}
        forumCategory={forumTopic.forum!.category}
        t_currentPageLabel={forumTopic.title as TranslatedString}
      />
      <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">{forumTopic.title}</h1>

      {can.updateForumTopic ? (
        <div className="mb-4 flex flex-col gap-2">
          <TopicOptions />
        </div>
      ) : null}

      <div className="flex items-center justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedForumTopicComments}
        />

        {auth ? (
          <SubscribeToggleButton
            subjectId={forumTopic.id}
            subjectType="ForumTopic"
            hasExistingSubscription={isSubscribed}
          />
        ) : null}
      </div>

      <div className="mt-2 flex flex-col gap-3">
        {paginatedForumTopicComments.items.map((comment) => (
          <ForumPostCard
            key={`comment-${comment.id}`}
            body={comment.body}
            canManage={can.manageForumTopicComments}
            canUpdate={
              can.manageForumTopicComments ||
              getCanUpdatePost(forumTopic, comment, auth?.user, accessibleTeamAccounts)
            }
            comment={comment}
            isHighlighted={ziggy.query.comment === String(comment.id)}
            topic={forumTopic}
          />
        ))}
      </div>

      <div className="mt-4">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedForumTopicComments}
        />
      </div>

      <div>
        {auth?.user.isMuted && auth.user.mutedUntil ? (
          <MutedMessage mutedUntil={auth.user.mutedUntil} />
        ) : null}

        {forumTopic.lockedAt ? (
          <div
            className={cn(
              'my-4 flex select-none flex-col items-center justify-center gap-2 rounded-md border bg-embed p-4 text-center sm:flex-row sm:gap-1.5',
              'border-neutral-800 text-neutral-300 light:border-neutral-300 light:text-neutral-900',
            )}
          >
            <LuLock className="size-5" />
            <p className="flex flex-col gap-1 font-medium sm:flex-row">
              {t('This topic is locked.')}

              {can.createForumTopicComments ? (
                <span className="italic">{t('As staff, you can still reply.')}</span>
              ) : null}
            </p>
          </div>
        ) : null}

        {can.createForumTopicComments ? (
          <div className="mt-4">
            <QuickReplyForm onPreview={initiatePreview} />
          </div>
        ) : null}

        {!auth?.user && !forumTopic.lockedAt ? <SignInMessage /> : null}

        {previewContent ? (
          <div data-testid="preview-content" className="mb-3 mt-7">
            <ForumPostCard body={previewContent} />
          </div>
        ) : null}
      </div>
    </div>
  );
};

function getCanUpdatePost(
  topic: App.Data.ForumTopic,
  post: App.Data.ForumTopicComment,
  user?: App.Data.User | null,
  accessibleTeamAccounts?: App.Data.User[] | null,
): boolean {
  if (!user || user.isMuted || topic.lockedAt) {
    return false;
  }

  // Users can edit their own posts.
  if (user.displayName === post.user?.displayName) {
    return true;
  }

  // Users can edit posts made by team accounts they have access to.
  if (accessibleTeamAccounts && post.user) {
    return accessibleTeamAccounts.some((ta) => ta.displayName === post.user?.displayName);
  }

  return false;
}
