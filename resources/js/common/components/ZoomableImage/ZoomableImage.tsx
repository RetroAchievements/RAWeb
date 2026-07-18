import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { cn } from '@/common/utils/cn';
import { getScreenshotImageRendering } from '@/common/utils/getScreenshotImageRendering';
import type { TranslatedString } from '@/types/i18next';

interface ZoomableImageProps {
  alt: TranslatedString;
  children: ReactNode;
  src: string;

  aspectRatio?: number;
  isPixelated?: boolean;
  srcWidth?: number | null;
}

export const ZoomableImage: FC<ZoomableImageProps> = ({
  alt,
  aspectRatio,
  children,
  src,
  srcWidth,
  isPixelated = true,
}) => {
  const { t } = useTranslation();

  const imageRendering = getScreenshotImageRendering(srcWidth, isPixelated);

  return (
    <BaseDialog>
      <BaseDialogTitle className="sr-only">{t('screenshot')}</BaseDialogTitle>

      <BaseDialogTrigger>{children}</BaseDialogTrigger>

      <BaseDialogContent
        className="max-w-5xl border-0 bg-transparent p-0 light:bg-transparent"
        shouldShowCloseButton={false}
      >
        {/* Both of these are needed for a11y. */}
        <BaseDialogTitle className="sr-only">{t('screenshot')}</BaseDialogTitle>
        <BaseDialogDescription className="sr-only">{t('screenshot')}</BaseDialogDescription>

        {/* Clicking anywhere in the dialog should close it. */}
        <BaseDialogClose asChild>
          <div
            className={cn(
              'relative h-[calc(100vh-220px)] w-full overflow-clip rounded-md',
              aspectRatio && 'flex items-center justify-center',
            )}
          >
            <img
              src={src}
              alt={alt}
              className={cn(aspectRatio ? '' : 'h-full w-full object-contain')}
              style={{
                imageRendering,
                ...(aspectRatio
                  ? {
                      // Use min() so the image is as large as possible while
                      // staying both within the container width and maintaining
                      // the target aspect ratio at the container height.
                      width: `min(100%, calc(calc(100vh - 220px) * ${aspectRatio}))`,
                      aspectRatio,
                    }
                  : {}),
              }}
            />
          </div>
        </BaseDialogClose>
      </BaseDialogContent>
    </BaseDialog>
  );
};
