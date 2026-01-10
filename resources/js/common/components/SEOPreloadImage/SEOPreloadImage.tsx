// eslint-disable-next-line no-restricted-imports -- the Head import is intentional in this file
import { Head } from '@inertiajs/react';
import type { FC } from 'react';

interface SEOPreloadImageProps {
  /**
   * Image URL to preload for LCP optimization.
   * This tells the browser to fetch the image as early as possible.
   * Critical for hero images, banners, and above-the-fold content.
   * For responsive images, this serves as the fallback src.
   * @example banner?.mobileSmAvif || game.imageIngameUrl
   */
  src: string;

  /**
   * Image MIME type for preload hint (helps browser decode faster).
   * @example "image/avif", "image/webp", "image/jpeg"
   */
  type: string;

  /**
   * Optional media query to conditionally preload based on viewport.
   * This is useful when different breakpoints will receive different
   * image sizes, ie: LG vs XL.
   * @example "(max-width: 640px)" for mobile only
   */
  media?: string;

  /**
   * Optional srcset for responsive image preloading.
   * Allows the browser to choose the appropriate image size based on viewport.
   * @example "banner-md.avif 1024w, banner-lg.avif 1280w, banner-xl.avif 1920w"
   */
  imageSrcSet?: string;

  /**
   * Optional sizes attribute for responsive preloading.
   * Describes how wide the image will be at different viewport widths.
   * @example "100vw" or "(max-width: 768px) 100vw, 50vw"
   */
  imageSizes?: string;
}

/**
 * Preloads a critical image to improve LCP and Core Web Vitals.
 * Only preload the most important above-the-fold image - typically the hero/banner.
 *
 * 🔴 DO NOT preload multiple images on a page.
 * 🔴 This dilutes browser priority and hurts LCP.
 *
 * @example
 * // Simple preload
 * <SEOPreloadImage
 *   src={banner?.mobileSmAvif ?? game.imageIngameUrl}
 *   type="image/avif"
 * />
 *
 * // Responsive preload
 * <SEOPreloadImage
 *   src={banner.desktopMdAvif}
 *   srcSet="banner-md.avif 1024w, banner-lg.avif 1280w, banner-xl.avif 1920w"
 *   sizes="100vw"
 *   type="image/avif"
 * />
 */
export const SEOPreloadImage: FC<SEOPreloadImageProps> = ({
  imageSizes,
  imageSrcSet,
  media,
  src,
  type,
}) => {
  return (
    <Head>
      <link
        rel="preload"
        as="image"
        href={src}
        type={type}
        media={media}
        imageSrcSet={imageSrcSet}
        imageSizes={imageSizes}
      />
    </Head>
  );
};
