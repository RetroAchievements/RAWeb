import { AnimatePresence, useReducedMotion } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuFlag } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { InertiaLink } from '@/common/components/InertiaLink';
import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { BlockedPostNotice } from './BlockedPostNotice';
import { ForumPostAuthorBox } from './ForumPostAuthorBox';
import { ForumPostCardMeta } from './ForumPostCardMeta';
import { ForumPostCopyLinkButton } from './ForumPostCopyLinkButton';
import { ForumPostManage } from './ForumPostManage';

const REVEAL_EASE = [0.23, 1, 0.32, 1] as const;
const REVEAL_DURATION = 0.2;

interface ForumPostCardProps {
  body: string;

  canManage?: boolean;
  canUpdate?: boolean;
  comment?: App.Data.ForumTopicComment;
  isHighlighted?: boolean;
  topic?: App.Data.ForumTopic;
}

export const ForumPostCard: FC<ForumPostCardProps> = ({
  body,
  comment,
  topic,
  canManage = false,
  canUpdate = false,
  isHighlighted = false,
}) => {
  const { can } = usePageProps<App.Community.Data.MessageThreadShowPageProps>();

  const [isRevealed, setIsRevealed] = useState(false);

  const prefersReducedMotion = useReducedMotion();

  const revealTransition = prefersReducedMotion
    ? { height: { duration: 0 }, opacity: { duration: 0.15 } }
    : { duration: REVEAL_DURATION, ease: REVEAL_EASE };

  const canRevealBlockedPosts = !!can?.manageForumTopicComments;
  const isBlocked = !!comment?.isFromBlockedUser;
  const isMasked = isBlocked && !isRevealed;

  const postContent = (
    <ForumPostContent
      body={body}
      canManage={canManage}
      canUpdate={canUpdate}
      comment={comment}
      topic={topic}
    />
  );

  return (
    <div id={comment?.id ? `${comment.id}` : undefined} className="scroll-mt-14">
      <div className="relative">
        <div
          className={cn(
            'relative -mx-2 w-[calc(100%+16px)] rounded-lg bg-embed-highlight px-1 py-2 even:bg-embed',
            'light:border light:border-neutral-300 light:bg-white',
            'sm:mx-0 sm:w-full',

            isBlocked ? null : 'lg:flex',
            isHighlighted ? 'outline-2' : null,
          )}
        >
          {!isBlocked ? postContent : null}

          {isBlocked && !canRevealBlockedPosts ? <BlockedPostNotice /> : null}

          {isBlocked && canRevealBlockedPosts ? (
            <>
              <AnimatePresence initial={false}>
                {isMasked ? (
                  <m.div
                    key="notice"
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={revealTransition}
                    className="w-full overflow-hidden"
                  >
                    <BlockedPostNotice
                      authorDisplayName={comment?.user?.displayName ?? ''}
                      onReveal={() => setIsRevealed(true)}
                    />
                  </m.div>
                ) : null}
              </AnimatePresence>

              <m.div
                data-testid="revealable-post"
                aria-hidden={!isRevealed}
                inert={!isRevealed}
                initial={false}
                animate={{ height: isRevealed ? 'auto' : 0, opacity: isRevealed ? 1 : 0 }}
                transition={revealTransition}
                className="w-full overflow-hidden"
              >
                <div className="lg:flex">{postContent}</div>
              </m.div>
            </>
          ) : null}
        </div>
      </div>
    </div>
  );
};

interface ForumPostContentProps {
  body: string;
  canManage: boolean;
  canUpdate: boolean;

  comment?: App.Data.ForumTopicComment;
  topic?: App.Data.ForumTopic;
}

const ForumPostContent: FC<ForumPostContentProps> = ({
  body,
  canManage,
  canUpdate,
  comment,
  topic,
}) => {
  const { auth, can } = usePageProps<App.Community.Data.MessageThreadShowPageProps>();
  const { t } = useTranslation();

  const canReport =
    can?.createModerationReports && comment?.user?.displayName !== auth?.user.displayName;

  return (
    <>
      <ForumPostAuthorBox comment={comment} />

      <div className="w-full py-2 lg:px-6 lg:py-2" style={{ wordBreak: 'break-word' }}>
        <div className="mb-4 flex w-full items-center justify-between gap-x-2 gap-y-2 sm:items-start lg:mb-3">
          {comment && topic ? (
            <ForumPostCardMeta comment={comment} topic={topic} />
          ) : (
            <p className="text-2xs leading-3.5 text-neutral-400">{t('Preview')}</p>
          )}

          {comment && topic ? (
            <div className="flex items-center gap-x-1 lg:-mx-4 lg:pl-4">
              {!comment.isAuthorized && canManage ? <ForumPostManage comment={comment} /> : null}

              {canUpdate ? (
                <InertiaLink
                  href={route('forum-topic-comment.edit', {
                    comment: comment.id,
                  })}
                  className={baseButtonVariants({
                    size: 'sm',
                    className: 'max-h-5.5 p-1! text-2xs! lg:text-xs!',
                  })}
                >
                  {t('Edit')}
                </InertiaLink>
              ) : null}

              {canReport ? (
                <InertiaLink
                  href={route('message-thread.create', {
                    to: 'RAdmin',
                    subject: `Report: Forum Post by ${comment.user?.displayName}`,
                    rType: 'ForumTopicComment',
                    rId: comment.id,
                  })}
                  className={baseButtonVariants({
                    size: 'sm',
                    className: 'max-h-5.5 gap-1 p-1! text-2xs! lg:text-xs!',
                  })}
                >
                  <LuFlag className="size-3" />
                  {t('Report')}
                </InertiaLink>
              ) : null}

              <ForumPostCopyLinkButton comment={comment} topic={topic} />
            </div>
          ) : null}
        </div>

        <div>
          <ShortcodeRenderer body={body} />
        </div>
      </div>
    </>
  );
};
