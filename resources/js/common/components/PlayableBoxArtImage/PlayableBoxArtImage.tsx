import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { ZoomableImage } from '@/common/components/ZoomableImage';

interface PlayableBoxArtImageProps {
  src?: string;
}

export const PlayableBoxArtImage: FC<PlayableBoxArtImageProps> = ({ src }) => {
  const { t } = useTranslation();

  if (!src || src.includes('000002')) {
    return null;
  }

  return (
    <div className="overflow-hidden text-center">
      <ZoomableImage src={src} alt={t('boxart')}>
        <img className="max-w-full rounded-sm" src={src} alt={t('boxart')} />
      </ZoomableImage>
    </div>
  );
};
