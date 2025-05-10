import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { cn } from '@/common/utils/cn';

import { ForumPostAuthorBox } from './ForumPostAuthorBox';
import { ForumPostCardMeta } from './ForumPostCardMeta';
import { ForumPostCopyLinkButton } from './ForumPostCopyLinkButton';
import { ForumPostManage } from './ForumPostManage';

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
  const { t } = useTranslation();

  return (
    <div id={comment?.id ? `${comment.id}` : undefined} className="scroll-mt-14">
      <div className="relative">
        <div
          className={cn(
            'relative -mx-2 w-[calc(100%+16px)] rounded-lg bg-embed-highlight px-1 py-2 even:bg-embed sm:mx-0 sm:w-full lg:flex',
            isHighlighted ? 'outline outline-2' : null,
          )}
        >
          <ForumPostAuthorBox comment={comment} />

          <div className="w-full py-2 lg:px-6 lg:py-2" style={{ wordBreak: 'break-word' }}>
            <div className="mb-4 flex w-full items-start justify-between gap-x-2 gap-y-2 lg:mb-3">
              {comment && topic ? (
                <ForumPostCardMeta comment={comment} topic={topic} />
              ) : (
                <p className="text-2xs leading-[14px] text-neutral-400">{t('Preview')}</p>
              )}

              {comment && topic ? (
                <div className="flex items-center gap-x-1 lg:-mx-4 lg:pl-4">
                  {!comment.isAuthorized && canManage ? (
                    <ForumPostManage comment={comment} />
                  ) : null}

                  {canUpdate ? (
                    <a
                      href={route('forum-topic-comment.edit', { comment: comment.id })}
                      className={baseButtonVariants({
                        size: 'sm',
                        className: 'max-h-[22px] !p-1 !text-2xs lg:!text-xs',
                      })}
                    >
                      {t('Edit')}
                    </a>
                  ) : null}

                  <ForumPostCopyLinkButton comment={comment} topic={topic} />
                </div>
              ) : null}
            </div>

            <div>
              <ShortcodeRenderer body={body} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
