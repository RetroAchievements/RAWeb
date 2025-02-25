import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';

import { ZoomableImage } from '../ZoomableImage';

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
      <ZoomableImage src={imageTitleUrl} alt={t('title screenshot')}>
        <div className="flex items-center justify-center overflow-hidden">
          <img className="w-full rounded-sm" src={imageTitleUrl} alt={t('title screenshot')} />
        </div>
      </ZoomableImage>

      <ZoomableImage src={imageIngameUrl} alt={t('ingame screenshot')}>
        <div className="flex items-center justify-center overflow-hidden">
          <img className="w-full rounded-sm" src={imageIngameUrl} alt={t('ingame screenshot')} />
        </div>
      </ZoomableImage>
    </div>
  );
};
