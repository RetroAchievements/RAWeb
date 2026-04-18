import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleCheckBig, LuCircleX } from 'react-icons/lu';

interface ScreenshotPreviewMetaProps {
  height: number;
  isResolutionValid: boolean;
  width: number;
}

export const ScreenshotPreviewMeta: FC<ScreenshotPreviewMetaProps> = ({
  height,
  isResolutionValid,
  width,
}) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-3 text-xs">
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
  );
};
