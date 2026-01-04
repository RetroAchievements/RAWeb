// eslint-disable-next-line no-restricted-imports -- the Head import is intentional in this file
import { Head } from '@inertiajs/react';
import type { FC } from 'react';

interface SEOPreloadImageProps {
  /**
   * Image URL to preload for LCP optimization.
   * This tells the browser to fetch the image as early as possible.
   * Critical for hero images, banners, and above-the-fold content.
   * @example game.banner?.mobileSmAvif || game.imageIngameUrl
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
}

/**
 * Preloads a critical image to improve LCP and Core Web Vitals.
 * Only preload the most important above-the-fold image - typically the hero/banner.
 *
 * 🔴 DO NOT preload multiple images on a page.
 * 🔴 This dilutes browser priority and hurts LCP.
 *
 * @example
 * <SEOPreloadImage
 *   src={game.banner?.mobileSmAvif ?? game.imageIngameUrl}
 *   type="image/avif"
 * />
 */
export const SEOPreloadImage: FC<SEOPreloadImageProps> = ({ src, type, media }) => {
  return (
    <Head>
      <link rel="preload" as="image" href={src} type={type} media={media} />
    </Head>
  );
};
