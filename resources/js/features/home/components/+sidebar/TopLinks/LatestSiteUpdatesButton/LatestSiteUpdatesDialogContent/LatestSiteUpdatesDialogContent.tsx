import type { FC } from 'react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronRight } from 'react-icons/lu';

import {
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { MarkdownRenderer } from '@/common/components/MarkdownRenderer';
import { useMarkAsViewedMutation } from '@/common/hooks/mutations/useMarkAsViewedMutation';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface LatestSiteUpdatesDialogContentProps {
  latestNewsId?: number;
}

export const LatestSiteUpdatesDialogContent: FC<LatestSiteUpdatesDialogContentProps> = ({
  latestNewsId,
}) => {
  const { auth, deferredSiteReleaseNotes, hasUnreadSiteReleaseNote } =
    usePageProps<App.Http.Data.HomePageProps>();
  const { t } = useTranslation();

  const markAsViewedMutation = useMarkAsViewedMutation();

  // Mark the latest release notes entry as viewed when the dialog opens.
  useEffect(() => {
    if (auth?.user && latestNewsId && hasUnreadSiteReleaseNote) {
      markAsViewedMutation.mutate({
        viewableId: latestNewsId,
        viewableType: 'news',
      });
    }
  }, [latestNewsId, auth?.user, markAsViewedMutation, hasUnreadSiteReleaseNote]);

  return (
    <BaseDialogContent className="flex h-full max-w-[52rem] flex-col overflow-auto px-0 pb-0 sm:max-h-[60vh]">
      <BaseDialogHeader className="px-4">
        <BaseDialogTitle>{t('Latest Site Updates')}</BaseDialogTitle>

        <BaseDialogDescription className="sr-only">
          {t('Latest Site Updates')}
        </BaseDialogDescription>
      </BaseDialogHeader>

      {!deferredSiteReleaseNotes || !Array.isArray(deferredSiteReleaseNotes) ? (
        <div className="flex items-center justify-center py-8 text-neutral-400">
          {t('Loading...')}
        </div>
      ) : (
        <div className="flex flex-col gap-1 overflow-y-auto px-4">
          {deferredSiteReleaseNotes.map((note) => (
            <div
              key={note.id}
              className="mb-5 rounded-xl border border-neutral-800 bg-neutral-950 p-4 light:border-neutral-200 light:bg-white"
            >
              <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                  <p className="text-lg font-semibold">{note.title}</p>
                  <time className="shrink-0 text-2xs text-neutral-400">
                    {formatDate(note.createdAt, 'll')}
                  </time>
                </div>

                <MarkdownRenderer>{note.body}</MarkdownRenderer>

                {note.link ? (
                  <div className="flex w-full justify-end">
                    <a
                      href={note.link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-0.5 text-sm"
                    >
                      {t('See full release notes')}
                      <LuChevronRight className="-mb-0.5" />
                    </a>
                  </div>
                ) : null}
              </div>
            </div>
          ))}
        </div>
      )}
    </BaseDialogContent>
  );
};
