/* eslint-disable testing-library/no-container -- needed to test <head> tag metadata */

import { render } from '@/test';

import { SEOPreloadImage } from './SEOPreloadImage';

describe('Component: SEOPreloadImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadImage src="https://example.com/image.webp" type="image/webp" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a preload link with the correct attributes', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadImage src="https://example.com/banner.webp" type="image/webp" />,
    );

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).toBeInTheDocument();
    expect(preloadLink?.getAttribute('as')).toEqual('image');
    expect(preloadLink?.getAttribute('href')).toEqual('https://example.com/banner.webp');
    expect(preloadLink?.getAttribute('type')).toEqual('image/webp');
  });

  it('given an avif type is provided, uses that type', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadImage src="https://example.com/banner.avif" type="image/avif" />,
    );

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink?.getAttribute('type')).toEqual('image/avif');
  });

  it('given a media query is provided, includes it in the preload link', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadImage
        src="https://example.com/mobile-banner.webp"
        type="image/webp"
        media="(max-width: 640px)"
      />,
    );

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink?.getAttribute('media')).toEqual('(max-width: 640px)');
  });

  it('given no media query is provided, the media attribute is not present', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadImage src="https://example.com/banner.webp" type="image/webp" />,
    );

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toHaveAttribute('media');
  });
});
