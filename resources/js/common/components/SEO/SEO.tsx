// eslint-disable-next-line no-restricted-imports -- intentional in this file
import { Head, usePage } from '@inertiajs/react';
import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

interface SEOProps {
  // Base metadata.
  /**
   * The page title. This appears in search results, browser tabs, and Discord embeds.
   * Should be descriptive. Does not need to include the site name.
   * Google really likes when titles are 20-70 characters long.
   * @example "Sonic the Hedgehog"
   */
  title: TranslatedString;

  /**
   * A concise description of the page content.
   * This appears in search results and social media embeds.
   * Aim for 150-160 characters.
   */
  description: string;

  /**
   * Override the default canonical URL.
   * Only needed if the same content exists at multiple URLs (ie: a page that supports lots of query params).
   * @default Generated from the current URL.
   */
  canonical?: string;

  /**
   * The type of content on the page. Helps crawlers better understand what they're reading.
   * - 'website': General web pages.
   * - 'article': Blog posts, news articles.
   * - 'profile': User profiles.
   * @default "website"
   */
  type?: 'website' | 'article' | 'profile';

  // Open Graph protocol metadata.
  /**
   * Custom title for Open Graph (some social media embeds prioritize this).
   * @default Same as `title`.
   */
  ogTitle?: string;

  /**
   * Custom description for Open Graph (some social media embeds prioritize this).
   * @default Same as `description`.
   */
  ogDescription?: string;

  /**
   * Image to display when shared/embedded on social media.
   * Recommended size: 1200x630 pixels.
   * Must be an absolute URL.
   */
  ogImage?: string;

  /**
   * Site name for Open Graph shares/embeds.
   * @default "RetroAchievements"
   */
  ogSiteName?: string;

  // Twitter specific metadata.
  /**
   * Twitter card type.
   * - 'summary': Square image.
   * - 'summary_large_image': Large rectangular image.
   * @default "summary_large_image"
   */
  twitterCard?: 'summary' | 'summary_large_image';

  /**
   * Custom title for Twitter shares.
   * @default Same as `ogTitle`.
   */
  twitterTitle?: string;

  /**
   * Custom description for Twitter shares.
   * @default Same as `ogDescription`.
   */
  twitterDescription?: string;

  /**
   * Image to display in Twitter shares.
   * @default Same as `ogImage`.
   */
  twitterImage?: string;

  /**
   * Twitter handle of our website account.
   * @default "@RetroCheevos"
   */
  twitterSite?: string;

  // Article specific metadata (only used if `type` is "article").
  /**
   * When the article was first published.
   * Must be ISO-8601 format.
   * @example "2024-01-01T00:00:00Z"
   */
  publishedTime?: string;

  /**
   * When the article was last modified.
   * Must be ISO-8601 format.
   * @example "2024-01-01T00:00:00Z"
   */
  modifiedTime?: string;

  /**
   * Article author displayNames. Used for article schema and Open Graph author tags.
   */
  authors?: string[];

  /**
   * Article section/category.
   * @example "Game Guides", "News"
   */
  section?: string;

  /**
   * Article tags/keywords.
   */
  tags?: string[];

  /**
   * Custom structured data (JSON-LD).
   * Greatly improves Google SEO juice.
   * @see https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data
   */
  jsonLd?: Record<string, unknown>;
}

/**
 * SEO component for managing page metadata and improving social media sharing embeds.
 * It handles Open Graph tags (for Facebook/Discord), Twitter cards, and structured data.
 *
 * @example
 * // Basic usage
 * <SEO
 *   title="Sonic the Hedgehog"
 *   description="There are 36 achievements worth 305 points. Sonic the Hedgehog for Genesis/Mega Drive - explore and compete on this classic game at RetroAchievements."
 * />
 */
export const SEO: FC<SEOProps> = ({
  title,
  description,
  canonical,
  type = 'website',

  // OG defaults to base metadata if not provided.
  ogTitle = title,
  ogDescription = description,
  ogImage = '/assets/images/favicon.webp',
  ogSiteName = 'RetroAchievements',

  // Twitter defaults to OG if not provided.
  twitterCard = 'summary_large_image',
  twitterTitle = ogTitle,
  twitterDescription = ogDescription,
  twitterImage = ogImage,
  twitterSite = '@RetroCheevos',

  // Article metadata.
  /** ISO-8601 timestamp */
  publishedTime,
  /** ISO-8601 timestamp */
  modifiedTime,
  authors,
  section,
  tags,

  // Optional structured data.
  jsonLd,
}) => {
  const { constructedCanonicalUrl } = useConstructedCanonicalUrl();

  const resolvedCanonicalUrl = canonical ?? constructedCanonicalUrl;

  const defaultJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: title,
    description,
    url: resolvedCanonicalUrl,
  };

  const isArticle = type === 'article';

  return (
    <Head>
      {/* Base metadata */}
      <meta name="description" content={description} />
      <link rel="canonical" href={resolvedCanonicalUrl} />

      {/* Open Graph metadata */}
      <meta property="og:site_name" content={ogSiteName} />
      <meta property="og:type" content={type} />
      <meta property="og:title" content={ogTitle} />
      <meta property="og:description" content={ogDescription} />
      <meta property="og:url" content={resolvedCanonicalUrl} />
      {ogImage ? <meta property="og:image" content={ogImage} /> : null}

      {/* Twitter metadata */}
      <meta name="twitter:card" content={twitterCard} />
      <meta name="twitter:title" content={twitterTitle} />
      <meta name="twitter:description" content={twitterDescription} />
      {twitterImage ? <meta name="twitter:image" content={twitterImage} /> : null}
      <meta name="twitter:site" content={twitterSite} />

      {/* Article specific metadata */}
      {isArticle && publishedTime ? (
        <meta property="article:published_time" content={publishedTime} />
      ) : null}
      {isArticle && modifiedTime ? (
        <meta property="article:modified_time" content={modifiedTime} />
      ) : null}
      {isArticle && section ? <meta property="article:section" content={section} /> : null}
      {isArticle && authors?.length
        ? authors.map((author) => (
            <meta key={`head-author-${author}`} property="article:author" content={author} />
          ))
        : null}
      {isArticle && tags?.length
        ? tags.map((tag) => <meta key={`head-tag-${tag}`} property="article:tag" content={tag} />)
        : null}

      {/* JSON-LD structured data */}
      <script type="application/ld+json">{JSON.stringify(jsonLd || defaultJsonLd)}</script>
    </Head>
  );
};

function useConstructedCanonicalUrl() {
  const page = usePage();
  const { config } = usePageProps();

  const baseUrl = config.app.url;
  const path = page.url;

  // Strip potential duplicative slashes from both URLs if they're present,
  // then join them with a slash ("/") between them.
  const constructedCanonicalUrl = `${baseUrl.replace(/\/+$/, '')}/${path.replace(/^\/+/, '')}`;

  return { constructedCanonicalUrl };
}
