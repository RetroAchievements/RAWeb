import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseCarousel,
  BaseCarouselContent,
  BaseCarouselItem,
} from '@/common/components/+vendor/BaseCarousel';

interface PlayableMobileMediaCarouselProps {
  imageTitleUrl: string;
  imageIngameUrl: string;
}

export const PlayableMobileMediaCarousel: FC<PlayableMobileMediaCarouselProps> = ({
  imageIngameUrl,
  imageTitleUrl,
}) => {
  const { t } = useTranslation();

  return (
    <BaseCarousel className="bg-embed py-1.5">
      <BaseCarouselContent>
        <BaseCarouselItem className="basis-4/5">
          <img src={imageTitleUrl} alt={t('title screenshot')} />
        </BaseCarouselItem>

        <BaseCarouselItem className="basis-4/5">
          <img src={imageIngameUrl} alt={t('ingame screenshot')} />
        </BaseCarouselItem>
      </BaseCarouselContent>
    </BaseCarousel>
  );
};
