import * as m from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuTrash2 } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { TranslatedString } from '@/types/i18next';

interface PendingSubmissionsListProps {
  onCancel: (screenshotId: number) => void;
  submissions: App.Platform.Data.GameScreenshot[];
}

export const PendingSubmissionsList: FC<PendingSubmissionsListProps> = ({
  onCancel,
  submissions,
}) => {
  const { t } = useTranslation();

  const screenshotTypeLabels: Record<App.Platform.Enums.ScreenshotType, TranslatedString> = {
    title: t('Title'),
    ingame: t('In-game'),
    completion: t('Completion'),
  };

  if (submissions.length === 0) {
    return <p className="text-xs text-neutral-400">{t('No pending submissions for this game.')}</p>;
  }

  const handleCancelClick = (screenshotId: number) => {
    if (!confirm(t('Are you sure you want to cancel this submission?'))) {
      return;
    }

    onCancel(screenshotId);
  };

  return (
    <div className="flex flex-col gap-2">
      {submissions.map((submission, index) => (
        <m.div
          key={submission.id}
          initial={{ opacity: 0, y: 6 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.2, delay: index * 0.05 }}
          className="flex items-center gap-3 rounded border border-neutral-700 p-2"
        >
          <img
            src={submission.smWebpUrl}
            alt="your upload" // intentionally untranslated
            className="h-12 w-16 rounded object-cover"
            loading="lazy"
          />

          <div className="flex flex-1 flex-col gap-1">
            <div className="flex items-center gap-2">
              <span className="text-xs font-medium">{screenshotTypeLabels[submission.type]}</span>

              <span className="rounded bg-yellow-900/30 px-1.5 py-0.5 text-[10px] text-yellow-400">
                {t('Pending')}
              </span>
            </div>

            <span className="text-[10px] text-neutral-500">
              {submission.width}x{submission.height}
            </span>
          </div>

          <BaseButton
            aria-label={t('Cancel submission')}
            size="sm"
            variant="destructive"
            onClick={() => handleCancelClick(submission.id)}
          >
            <LuTrash2 className="size-3.5" />
          </BaseButton>
        </m.div>
      ))}
    </div>
  );
};
