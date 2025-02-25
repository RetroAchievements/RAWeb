import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import type { TranslatedString } from '@/types/i18next';

interface ZoomableImageProps {
  children: ReactNode;
  src: string;
  alt: TranslatedString;
}

export const ZoomableImage: FC<ZoomableImageProps> = ({ alt, children, src }) => {
  const { t } = useTranslation();

  return (
    <BaseDialog>
      <BaseDialogTitle className="sr-only">{t('screenshot')}</BaseDialogTitle>

      <BaseDialogTrigger>{children}</BaseDialogTrigger>

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
