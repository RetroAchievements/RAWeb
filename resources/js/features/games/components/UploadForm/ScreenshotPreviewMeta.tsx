import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleCheckBig, LuCircleX, LuTriangleAlert } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface ScreenshotPreviewMetaProps {
  height: number;
  isResolutionValid: boolean;
  width: number;

  canonicalResolution?: string | null;
  hasConsistencyWarning?: boolean;
  is1xCapture?: boolean;
  screenshotResolutions?: Array<{ width: number; height: number }>;
  supportsUpscaledScreenshots?: boolean;
}

export const ScreenshotPreviewMeta: FC<ScreenshotPreviewMetaProps> = ({
  canonicalResolution,
  hasConsistencyWarning,
  height,
  is1xCapture,
  isResolutionValid,
  supportsUpscaledScreenshots,
  width,
  screenshotResolutions = [],
}) => {
  const { t } = useTranslation();

  const consistencyMessage = canonicalResolution
    ? t("Doesn't match existing screenshots ({{resolution}})", {
        resolution: canonicalResolution,
      })
    : t("Doesn't match existing screenshots");

  const invalidExplanation = supportsUpscaledScreenshots
    ? t("Use your emulator's screenshot tool, ideally at 2x or 3x internal resolution.")
    : t("Use your emulator's screenshot tool, not a desktop capture.");

  const showUpscaleNudge = isResolutionValid && supportsUpscaledScreenshots && is1xCapture;
  const showConsistencyWarning = isResolutionValid && hasConsistencyWarning && !showUpscaleNudge;

  const showInvalidTooltip = !isResolutionValid && screenshotResolutions.length > 0;

  return (
    <div className="flex flex-col items-center gap-1 text-xs">
      <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
        <span className="text-neutral-400">
          {width}x{height}
        </span>

        {isResolutionValid ? (
          <span className="flex items-center gap-1 text-green-500">
            <LuCircleCheckBig className="size-3" />
            {t('Valid resolution')}
          </span>
        ) : showInvalidTooltip ? (
          <BaseTooltip>
            <BaseTooltipTrigger asChild>
              <span className="flex items-center gap-1 text-red-500 underline decoration-dotted underline-offset-2">
                <LuCircleX className="size-3" />
                {t('Invalid resolution')}
              </span>
            </BaseTooltipTrigger>

            <BaseTooltipPortal>
              <BaseTooltipContent className="max-w-xs">
                <AcceptedSizesTooltip
                  screenshotResolutions={screenshotResolutions}
                  supportsUpscaledScreenshots={supportsUpscaledScreenshots}
                />
              </BaseTooltipContent>
            </BaseTooltipPortal>
          </BaseTooltip>
        ) : (
          <span className="flex items-center gap-1 text-red-500">
            <LuCircleX className="size-3" />
            {t('Invalid resolution')}
          </span>
        )}
      </div>

      {!isResolutionValid ? (
        <p className="text-balance text-center text-red-500/80">{invalidExplanation}</p>
      ) : null}

      {showUpscaleNudge ? (
        <div className="flex items-center gap-1 text-yellow-500">
          <LuTriangleAlert className="size-3 shrink-0" />
          <span>{t('1x capture, render at 2x or 3x for a sharper screenshot')}</span>
        </div>
      ) : null}

      {showConsistencyWarning ? (
        <div className="flex items-center gap-1 text-yellow-500">
          <LuTriangleAlert className="size-3 shrink-0" />
          <span>{consistencyMessage}</span>
        </div>
      ) : null}
    </div>
  );
};

interface AcceptedSizesTooltipProps {
  screenshotResolutions: Array<{ width: number; height: number }>;
  supportsUpscaledScreenshots?: boolean;
}

const AcceptedSizesTooltip: FC<AcceptedSizesTooltipProps> = ({
  screenshotResolutions,
  supportsUpscaledScreenshots,
}) => {
  const { t } = useTranslation();

  // Sort by width, then by height.
  const sortedResolutions = [...screenshotResolutions].sort(
    (a, b) => a.width - b.width || a.height - b.height,
  );
  const nativeList = sortedResolutions.map((r) => `${r.width}x${r.height}`).join(', ');

  return (
    <div className="flex flex-col gap-1 text-xs">
      <p>{nativeList}</p>
      {supportsUpscaledScreenshots ? (
        <p className="text-neutral-400">{t('or 2x or 3x of any of these')}</p>
      ) : null}
    </div>
  );
};
