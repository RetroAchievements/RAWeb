import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface BoxArtImageProps {
  event: App.Platform.Data.Event;
}

export const BoxArtImage: FC<BoxArtImageProps> = ({ event }) => {
  const { t } = useTranslation();

  if (!event.legacyGame?.imageBoxArtUrl || event.legacyGame.imageBoxArtUrl.includes('000002')) {
    return null;
  }

  return (
    <img
      className="max-w-full rounded-sm"
      src={event.legacyGame.imageBoxArtUrl}
      alt={t('boxart')}
    />
  );
};
