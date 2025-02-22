import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface EventMainMediaProps {
  imageTitleUrl: string;
  imageIngameUrl: string;
}

export const EventMainMedia: FC<EventMainMediaProps> = ({ imageIngameUrl, imageTitleUrl }) => {
  const { t } = useTranslation();

  // If both images are the "No Screenshot Found" default, display nothing
  if (imageTitleUrl.includes('000002') && imageIngameUrl.includes('000002')) {
    return null;
  }

  return (
    <div
      className={cn(
        'flex w-full items-center justify-around',
        'border border-embed-highlight bg-zinc-900/50 light:bg-embed',
        'gap-x-5 gap-y-1',
        'xl:mx-0 xl:min-h-[180px] xl:w-full xl:rounded-lg xl:px-4 xl:py-2',
      )}
    >
      <ZoomableImage src={imageTitleUrl} alt={t('title screenshot')} />
      <ZoomableImage src={imageIngameUrl} alt={t('ingame screenshot')} />
    </div>
  );
};

interface ZoomableImageProps {
  src: string;
  alt: TranslatedString;
}

const ZoomableImage: FC<ZoomableImageProps> = ({ src, alt }) => {
  const { t } = useTranslation();

  return (
    <BaseDialog>
      <BaseDialogTitle className="sr-only">{t('screenshot')}</BaseDialogTitle>

      <BaseDialogTrigger>
        <div className="flex items-center justify-center overflow-hidden">
          <img className="w-full rounded-sm" src={src} alt={alt} />
        </div>
      </BaseDialogTrigger>

      <BaseDialogContent
        className="max-w-5xl border-0 bg-transparent p-0"
        shouldShowCloseButton={false}
      >
        {/* Both of these are needed for a11y */}
        <BaseDialogTitle className="sr-only">{t('screenshot')}</BaseDialogTitle>
        <BaseDialogDescription className="sr-only">{t('screenshot')}</BaseDialogDescription>

        <div className="relative h-[calc(100vh-220px)] w-full overflow-clip rounded-md bg-transparent shadow-md">
          <img
            src={src}
            alt={alt}
            className="h-full w-full object-contain"
            style={{ imageRendering: 'pixelated' }}
          />
        </div>
      </BaseDialogContent>
    </BaseDialog>
  );
};
