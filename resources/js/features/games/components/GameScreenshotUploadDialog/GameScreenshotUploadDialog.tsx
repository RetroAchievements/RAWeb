import * as m from 'motion/react-m';
import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useDeleteGameScreenshotMutation } from '../../hooks/mutations/useDeleteGameScreenshotMutation';
import { ScreenshotSlotConfig } from '../../models';
import { screenshotSubmissionLimits } from '../../utils/screenshotSubmissionLimits';
import { PendingSubmissionsList } from '../PendingSubmissionsList';
import { ScreenshotSlotStatusIndicator } from '../ScreenshotSlotStatusIndicator';
import { UploadForm } from '../UploadForm';

const SLOT_CONFIGS: ScreenshotSlotConfig[] = [
  { type: 'title', label: 'Title' },
  { type: 'ingame', label: 'In-game' },
  { type: 'completion', label: 'Completion' },
];

interface GameScreenshotUploadDialogProps {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

export const GameScreenshotUploadDialog: FC<GameScreenshotUploadDialogProps> = ({
  isOpen,
  onOpenChange,
}) => {
  const {
    game,
    screenshotUploadStatuses,
    screenshotUploadPendingCount,
    screenshotUploadUserSubmissions,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [selectedType, setSelectedType] = useState<App.Platform.Enums.ScreenshotType>('ingame');
  const [submissions, setSubmissions] = useState(screenshotUploadUserSubmissions ?? []);
  const [currentPendingCount, setCurrentPendingCount] = useState(screenshotUploadPendingCount ?? 0);

  const deleteMutation = useDeleteGameScreenshotMutation();

  const handleCancel = (screenshotId: number) => {
    toastMessage.promise(
      deleteMutation.mutateAsync({ gameId: game.id, gameScreenshotId: screenshotId }),
      {
        loading: t('Deleting...'),
        success: () => {
          setSubmissions((prev) => prev.filter((s) => s.id !== screenshotId));
          setCurrentPendingCount((prev) => prev - 1);

          return t('Screenshot deleted successfully.');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  const handleUploadSuccess = (screenshot: App.Platform.Data.GameScreenshot) => {
    setCurrentPendingCount((prev) => prev + 1);
    setSubmissions((prev) => [screenshot, ...prev]);
  };

  const showPendingWarning =
    currentPendingCount >= screenshotSubmissionLimits.pendingWarningThreshold;
  const statuses = screenshotUploadStatuses ?? {};

  return (
    <BaseDialog open={isOpen} onOpenChange={onOpenChange}>
      <BaseDialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-md">
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Upload Screenshot')}</BaseDialogTitle>
          <BaseDialogDescription className="sr-only">{game.title}</BaseDialogDescription>
        </BaseDialogHeader>

        <div className="flex flex-col gap-4">
          {showPendingWarning ? (
            <p className="rounded-lg border border-yellow-800 bg-yellow-950/30 px-3 py-2 text-xs text-yellow-300">
              {t('You have {{count}} of {{max}} pending submissions.', {
                count: currentPendingCount,
                max: screenshotSubmissionLimits.maxPendingSubmissions,
              })}
            </p>
          ) : null}

          {/* Screenshot type slot cards */}
          <div className="grid grid-cols-3 gap-2">
            {SLOT_CONFIGS.map((slot) => {
              const typeStatus = statuses[slot.type];
              const isSelected = selectedType === slot.type;

              return (
                <button
                  key={slot.type}
                  type="button"
                  onClick={() => setSelectedType(slot.type)}
                  className={cn(
                    'flex flex-col items-center gap-1 rounded-lg border px-3 py-2.5 text-center transition-all',
                    isSelected
                      ? 'border-neutral-200 bg-neutral-800 text-neutral-50'
                      : 'border-neutral-700 hover:border-neutral-500 light:border-neutral-300 light:hover:border-neutral-400',
                  )}
                >
                  <span
                    className={cn(
                      'text-sm font-medium',
                      isSelected ? 'text-neutral-50' : 'text-neutral-300 light:text-neutral-700',
                    )}
                  >
                    {slot.label}
                  </span>

                  <ScreenshotSlotStatusIndicator typeStatus={typeStatus} />
                </button>
              );
            })}
          </div>

          {/* Upload drop zone */}
          <UploadForm
            gameId={game.id}
            screenshotResolutions={game.system?.screenshotResolutions ?? []}
            selectedType={selectedType}
            hasAnalogTvOutput={game.system?.hasAnalogTvOutput}
            supportsUpscaledScreenshots={game.system?.supportsUpscaledScreenshots}
            onSuccess={handleUploadSuccess}
          />

          {/* Pending submissions */}
          {submissions.length > 0 ? (
            <m.div
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.25 }}
              className="flex flex-col"
            >
              <h3 className="mb-1 text-xs font-medium text-neutral-400">{t('Your Submissions')}</h3>

              <PendingSubmissionsList submissions={submissions} onCancel={handleCancel} />
            </m.div>
          ) : null}
        </div>
      </BaseDialogContent>
    </BaseDialog>
  );
};
