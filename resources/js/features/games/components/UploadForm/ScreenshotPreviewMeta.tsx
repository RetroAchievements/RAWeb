import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleCheckBig, LuCircleX, LuTriangleAlert } from 'react-icons/lu';

interface ScreenshotPreviewMetaProps {
  height: number;
  isResolutionValid: boolean;
  width: number;

  canonicalResolution?: string | null;
  hasConsistencyWarning?: boolean;
}

export const ScreenshotPreviewMeta: FC<ScreenshotPreviewMetaProps> = ({
  canonicalResolution,
  hasConsistencyWarning,
  height,
  isResolutionValid,
  width,
}) => {
  const { t } = useTranslation();

  const consistencyMessage = canonicalResolution
    ? t("Doesn't match existing screenshots ({{resolution}})", {
        resolution: canonicalResolution,
      })
    : t("Doesn't match existing screenshots");

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
        ) : (
          <span className="flex items-center gap-1 text-red-500">
            <LuCircleX className="size-3" />
            {t('Invalid resolution')}
          </span>
        )}
      </div>

      {isResolutionValid && hasConsistencyWarning ? (
        <div className="flex items-center gap-1 text-yellow-500">
          <LuTriangleAlert className="size-3 shrink-0" />
          <span>{consistencyMessage}</span>
        </div>
      ) : null}
    </div>
  );
};
