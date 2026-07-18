import type { TFunction } from 'i18next';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleCheckBig, LuCircleX, LuTriangleAlert } from 'react-icons/lu';

interface ScreenshotPreviewMetaProps {
  height: number;
  isResolutionValid: boolean;
  width: number;

  hasConsistencyWarning?: boolean;
  is1xCapture?: boolean;
  selectedType?: App.Platform.Enums.ScreenshotType;
  supportsUpscaledScreenshots?: boolean;
}

export const ScreenshotPreviewMeta: FC<ScreenshotPreviewMetaProps> = ({
  hasConsistencyWarning,
  height,
  is1xCapture,
  isResolutionValid,
  selectedType,
  supportsUpscaledScreenshots,
  width,
}) => {
  const { t } = useTranslation();

  const invalidExplanation = supportsUpscaledScreenshots
    ? t(
        "This doesn't look like a native capture. Use your emulator's screenshot tool at native, 2x, or 3x internal resolution, not a desktop capture or manual resize.",
      )
    : t(
        "This doesn't look like a native capture. Use your emulator's screenshot tool at native resolution, not a desktop capture or manual resize.",
      );

  const showUpscaleNudge = isResolutionValid && supportsUpscaledScreenshots && is1xCapture;
  const showConsistencyNudge = isResolutionValid && hasConsistencyWarning && !showUpscaleNudge;

  return (
    <div className="flex flex-col items-center gap-1 text-xs">
      <div className="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
        {isResolutionValid ? (
          <span className="flex items-center gap-1 text-green-500">
            <LuCircleCheckBig className="size-3" />
            {t('Valid resolution')}
          </span>
        ) : (
          <span className="flex items-center gap-1 text-red-500">
            <LuCircleX className="size-3" />
            {t('Invalid resolution')}
          </span>
        )}

        <span className="text-neutral-400">
          {width}x{height}
        </span>
      </div>

      {!isResolutionValid ? (
        <p className="text-center text-balance text-red-500/80">{invalidExplanation}</p>
      ) : null}

      {showUpscaleNudge ? (
        <div className="flex items-center gap-1 text-yellow-500">
          <LuTriangleAlert className="size-3 shrink-0" />
          <span>{t('1x capture, render at 2x or 3x for a sharper screenshot')}</span>
        </div>
      ) : null}

      {showConsistencyNudge ? (
        <p className="text-center text-balance text-neutral-400">
          {buildConsistencyNudgeMessage(selectedType, t)}
        </p>
      ) : null}
    </div>
  );
};

function buildConsistencyNudgeMessage(
  selectedType: App.Platform.Enums.ScreenshotType | undefined,
  t: TFunction,
): string {
  if (selectedType === 'title') {
    return t(
      'Submit this first, then submit a matching in-game screenshot at this resolution. Pairs are more likely to be accepted.',
    );
  }

  if (selectedType === 'ingame') {
    return t(
      'Submit this first, then submit a matching title screenshot at this resolution. Pairs are more likely to be accepted.',
    );
  }

  return t(
    'Submit this first, then submit matching screenshots at this resolution. Pairs are more likely to be accepted.',
  );
}
