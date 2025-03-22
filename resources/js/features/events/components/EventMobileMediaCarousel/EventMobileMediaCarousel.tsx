import type { FC } from 'react';

import {
  BaseCarousel,
  BaseCarouselContent,
  BaseCarouselItem,
} from '@/common/components/+vendor/BaseCarousel';

interface EventMobileMediaCarouselProps {
  imageTitleUrl: string;
  imageIngameUrl: string;
}

export const EventMobileMediaCarousel: FC<EventMobileMediaCarouselProps> = ({
  imageIngameUrl,
  imageTitleUrl,
}) => {
  return (
    <BaseCarousel className="bg-embed py-1.5">
      <BaseCarouselContent>
        <BaseCarouselItem className="basis-4/5">
          <img src={imageTitleUrl} />
        </BaseCarouselItem>

        <BaseCarouselItem className="basis-4/5">
          <img src={imageIngameUrl} />
        </BaseCarouselItem>
      </BaseCarouselContent>
    </BaseCarousel>
  );
};
