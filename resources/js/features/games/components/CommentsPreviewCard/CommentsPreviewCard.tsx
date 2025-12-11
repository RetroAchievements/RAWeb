import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronRight } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useGameShowTabs } from '../../hooks/useGameShowTabs';

export const CommentsPreviewCard: FC = () => {
  const { auth, numComments, recentVisibleComments } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { setCurrentTab } = useGameShowTabs();

  // Filter out automated comments and then get unique users ordered by the most recent comment.
  const nonAutomatedComments = recentVisibleComments.filter((c) => !c.isAutomated);

  const recentFirstComments = [...nonAutomatedComments].reverse();
  const uniqueUsers: App.Data.User[] = [];
  const seenDisplayNames = new Set<string>();
  for (const comment of recentFirstComments) {
    if (!seenDisplayNames.has(comment.user.displayName)) {
      seenDisplayNames.add(comment.user.displayName);
      uniqueUsers.push(comment.user);
    }
  }

  const previewComment = recentFirstComments[0];

  const handleClick = () => {
    setCurrentTab('community', {
      // We want the back button to return the user to the previous tab.
      shouldPushHistory: true,
    });

    // Wait for the tab content to render, then scroll to the bottom of the comments list.
    setTimeout(() => {
      document.getElementById('comments')?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, 0);
  };

  if (!numComments || !previewComment) {
    return null;
  }

  return (
    <div
      className={cn(
        'flex w-full flex-col gap-3 rounded-lg bg-neutral-950 p-4',
        'text-left transition hover:border-neutral-600',
        'light:border light:border-neutral-300 light:bg-white light:hover:border-neutral-400',
      )}
    >
      {/* Avatars and comment count */}
      <div className="flex items-center justify-between gap-3">
        <UserAvatarStack users={uniqueUsers} maxVisible={6} size={24} canLinkToUsers={false} />

        <BaseChip
          className={cn(
            'bg-neutral-800 text-neutral-200',
            'light:bg-neutral-100 light:text-neutral-800',
          )}
        >
          {t('commentCount', { val: numComments, count: numComments })}
        </BaseChip>
      </div>

      {/* Comment preview */}
      <div className="flex flex-col gap-1">
        <p className="line-clamp-3 text-xs" style={{ wordBreak: 'break-word' }}>
          {previewComment.payload}
        </p>

        <div className="flex items-center gap-1 text-2xs text-neutral-500">
          <span>{previewComment.user.displayName}</span>
          <span>{'·'}</span>
          <DiffTimestamp
            at={previewComment.createdAt}
            asAbsoluteDate={auth?.user?.preferences?.prefersAbsoluteDates ?? false}
            enableTooltip={false}
          />
        </div>
      </div>

      <button className="flex items-center gap-1 text-xs text-link" onClick={handleClick}>
        <span>{t('View recent comments')}</span>
        <LuChevronRight className="size-4" />
      </button>
    </div>
  );
};
