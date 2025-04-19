import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { ZoomableImage } from '@/common/components/ZoomableImage';

interface BoxArtImageProps {
  event: App.Platform.Data.Event;
}

export const BoxArtImage: FC<BoxArtImageProps> = ({ event }) => {
  const { t } = useTranslation();

  if (!event.legacyGame?.imageBoxArtUrl || event.legacyGame.imageBoxArtUrl.includes('000002')) {
    return null;
  }

  return (
    <div className="overflow-hidden text-center">
      <ZoomableImage src={event.legacyGame.imageBoxArtUrl} alt={t('boxart')}>
        <img
          className="max-w-full rounded-sm"
          src={event.legacyGame.imageBoxArtUrl}
          alt={t('boxart')}
        />
      </ZoomableImage>
    </div>
  );
};
