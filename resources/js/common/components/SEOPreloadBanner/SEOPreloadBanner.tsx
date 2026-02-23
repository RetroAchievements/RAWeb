import type { FC } from 'react';

import { SEOPreloadImage } from '@/common/components/SEOPreloadImage';

interface SEOPreloadBannerProps {
  banner: App.Platform.Data.PageBanner | null;
  device: 'mobile' | 'desktop';
}

export const SEOPreloadBanner: FC<SEOPreloadBannerProps> = ({ banner, device }) => {
  if (!banner) {
    return null;
  }

  if (device === 'mobile' && banner.mobileSmAvif) {
    return (
      <SEOPreloadImage src={banner.mobileSmAvif} type="image/avif" media="(max-width: 767px)" />
    );
  }

  if (device === 'desktop' && banner.desktopMdAvif) {
    return (
      <SEOPreloadImage
        src={banner.desktopMdAvif}
        media="(min-width: 768px)"
        imageSrcSet={[
          banner.desktopMdAvif && `${banner.desktopMdAvif} 1024w`,
          banner.desktopLgAvif && `${banner.desktopLgAvif} 1280w`,
          banner.desktopXlAvif && `${banner.desktopXlAvif} 1920w`,
        ]
          .filter(Boolean)
          .join(', ')}
        imageSizes="100vw"
        type="image/avif"
      />
    );
  }

  return null;
};
