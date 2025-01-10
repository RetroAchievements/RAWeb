/* eslint-disable testing-library/no-container -- needed to test <head> tag metadata */

import { render } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { SEO } from './SEO';

describe('Component: SEO', () => {
  const defaultPageProps = {
    config: {
      app: { url: 'https://example.com' },
      services: { patreon: { userId: undefined } },
    },
  };

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SEO title={'Test Title' as TranslatedString} description="Test Description" />,
      {
        pageProps: defaultPageProps,
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given only required props, sets default metadata values', () => {
    // ARRANGE
    const { container } = render(
      <SEO title={'Test Title' as TranslatedString} description="Test Description" />,
      {
        pageProps: defaultPageProps,
      },
    );

    // ASSERT
    const ogTitle = container.querySelector('meta[property="og:title"]');
    const ogType = container.querySelector('meta[property="og:type"]');
    const twitterCard = container.querySelector('meta[name="twitter:card"]');

    expect(ogTitle?.getAttribute('content')).toEqual('Test Title');
    expect(ogType?.getAttribute('content')).toEqual('website');
    expect(twitterCard?.getAttribute('content')).toEqual('summary');
  });

  it('given a custom canonical URL, uses it instead of the constructed one', () => {
    // ARRANGE
    const customCanonical = 'https://custom-domain.com/custom-path';
    const { container } = render(
      <SEO
        title={'Test Title' as TranslatedString}
        description="Test Description"
        canonical={customCanonical}
      />,
      { pageProps: defaultPageProps },
    );

    // ASSERT
    const canonicalLink = container.querySelector('link[rel="canonical"]');
    expect(canonicalLink?.getAttribute('href')).toEqual(customCanonical);
  });

  it('given article type and metadata, renders article-specific meta tags', () => {
    // ARRANGE
    const { container } = render(
      <SEO
        title={'Test Title' as TranslatedString}
        description="Test Description"
        type="article"
        publishedTime="2023-01-01T00:00:00Z"
        modifiedTime="2023-01-02T00:00:00Z"
        authors={['John Doe']}
        section="Gaming"
        tags={['retro', 'gaming']}
      />,
      { pageProps: defaultPageProps },
    );

    // ASSERT
    const publishedTime = container.querySelector('meta[property="article:published_time"]');
    const modifiedTime = container.querySelector('meta[property="article:modified_time"]');
    const section = container.querySelector('meta[property="article:section"]');
    const author = container.querySelector('meta[property="article:author"]');
    const tags = container.querySelectorAll('meta[property="article:tag"]');

    expect(publishedTime?.getAttribute('content')).toEqual('2023-01-01T00:00:00Z');
    expect(modifiedTime?.getAttribute('content')).toEqual('2023-01-02T00:00:00Z');
    expect(section?.getAttribute('content')).toEqual('Gaming');
    expect(author?.getAttribute('content')).toEqual('John Doe');
    expect(tags).toHaveLength(2);
  });

  it('given custom Open Graph metadata, overrides the default values', () => {
    // ARRANGE
    const { container } = render(
      <SEO
        title={'Test Title' as TranslatedString}
        description="Test Description"
        ogTitle="Custom OG Title"
        ogDescription="Custom OG Description"
        ogImage="https://example.com/image.jpg"
      />,
      { pageProps: defaultPageProps },
    );

    // ASSERT
    const ogTitle = container.querySelector('meta[property="og:title"]');
    const ogDescription = container.querySelector('meta[property="og:description"]');
    const ogImage = container.querySelector('meta[property="og:image"]');

    expect(ogTitle?.getAttribute('content')).toEqual('Custom OG Title');
    expect(ogDescription?.getAttribute('content')).toEqual('Custom OG Description');
    expect(ogImage?.getAttribute('content')).toEqual('https://example.com/image.jpg');
  });

  it('given custom Twitter metadata, overrides the Open Graph fallbacks', () => {
    // ARRANGE
    const { container } = render(
      <SEO
        title={'Test Title' as TranslatedString}
        description="Test Description"
        ogTitle="OG Title"
        twitterTitle="Custom Twitter Title"
        twitterDescription="Custom Twitter Description"
        twitterCard="summary"
        twitterSite="@some_user_123_456"
      />,
      { pageProps: defaultPageProps },
    );

    // ASSERT
    const twitterTitle = container.querySelector('meta[name="twitter:title"]');
    const twitterDescription = container.querySelector('meta[name="twitter:description"]');
    const twitterCard = container.querySelector('meta[name="twitter:card"]');
    const twitterSite = container.querySelector('meta[name="twitter:site"]');

    expect(twitterTitle?.getAttribute('content')).toEqual('Custom Twitter Title');
    expect(twitterDescription?.getAttribute('content')).toEqual('Custom Twitter Description');
    expect(twitterCard?.getAttribute('content')).toEqual('summary');
    expect(twitterSite?.getAttribute('content')).toEqual('@some_user_123_456');
  });

  it('given a custom JSON-LD object, renders it in a script tag', () => {
    // ARRANGE
    const customJsonLd = {
      '@context': 'https://schema.org',
      '@type': 'Article',
      headline: 'Custom Article',
    };

    const { container } = render(
      <SEO
        title={'Test Title' as TranslatedString}
        description="Test Description"
        jsonLd={customJsonLd}
      />,
      { pageProps: defaultPageProps },
    );

    // ASSERT
    const scriptTag = container.querySelector('script[type="application/ld+json"]');
    expect(JSON.parse(scriptTag?.textContent || '')).toEqual(customJsonLd);
  });
});
