import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSquareTerminal } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { BaseDialog, BaseDialogTrigger } from '@/common/components/+vendor/BaseDialog';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import { LatestSiteUpdatesDialogContent } from './LatestSiteUpdatesDialogContent';

export const LatestSiteUpdatesButton: FC = () => {
  const { deferredSiteReleaseNotes, hasUnreadSiteReleaseNote } =
    usePageProps<App.Http.Data.HomePageProps>();
  const { t } = useTranslation();

  const [isShowingUnreadIndicator, setIsShowingUnreadIndicator] =
    useState(hasUnreadSiteReleaseNote);

  // Get the latest release notes news entity ID from the deferred collection when it loads.
  const latestNewsId =
    Array.isArray(deferredSiteReleaseNotes) && deferredSiteReleaseNotes.length > 0
      ? deferredSiteReleaseNotes[0].id
      : undefined;

  const handleOpenDialog = () => {
    setIsShowingUnreadIndicator(false);
  };

  return (
    <BaseDialog>
      <BaseDialogTrigger asChild>
        <button
          onClick={handleOpenDialog}
          className={cn(
            baseButtonVariants({ size: 'sm' }),
            'relative',
            buildTrackingClassNames('Click Top Link Latest Site Updates'),
          )}
        >
          <LuSquareTerminal className="mr-2 size-4 text-amber-400" />

          {t('Latest Site Updates')}

          {isShowingUnreadIndicator ? (
            <span className="absolute -right-1 -top-1 flex size-2" aria-label={t('(unread)')}>
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75" />
              <span className="relative inline-flex size-2 rounded-full bg-blue-500" />
            </span>
          ) : null}
        </button>
      </BaseDialogTrigger>

      <LatestSiteUpdatesDialogContent latestNewsId={latestNewsId} />
    </BaseDialog>
  );
};
