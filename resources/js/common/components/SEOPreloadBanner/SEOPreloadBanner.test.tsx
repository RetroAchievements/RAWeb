/* eslint-disable testing-library/no-container -- testing-library queries can't reach elements injected via Inertia's Head component */

import { render } from '@/test';
import { createPageBanner } from '@/test/factories';

import { SEOPreloadBanner } from './SEOPreloadBanner';

describe('Component: SEOPreloadBanner', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SEOPreloadBanner banner={createPageBanner()} device="desktop" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the banner is null, preloads nothing', () => {
    // ARRANGE
    const { container } = render(<SEOPreloadBanner banner={null} device="desktop" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toBeInTheDocument();
  });

  it('given a mobile device, preloads the mobile AVIF source', () => {
    // ARRANGE
    const banner = createPageBanner({
      mobileSmAvif: 'https://example.com/banner-mobile.avif',
    });

    const { container } = render(<SEOPreloadBanner banner={banner} device="mobile" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).toBeInTheDocument();
    expect(preloadLink?.getAttribute('href')).toEqual('https://example.com/banner-mobile.avif');
    expect(preloadLink?.getAttribute('type')).toEqual('image/avif');
    expect(preloadLink?.getAttribute('media')).toEqual('(max-width: 767px)');
  });

  it('given a mobile device, does not include a responsive srcSet', () => {
    // ARRANGE
    const banner = createPageBanner({
      mobileSmAvif: 'https://example.com/banner-mobile.avif',
    });

    const { container } = render(<SEOPreloadBanner banner={banner} device="mobile" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toHaveAttribute('imagesrcset');
    expect(preloadLink).not.toHaveAttribute('imagesizes');
  });

  it('given a desktop device, preloads with a responsive AVIF srcSet', () => {
    // ARRANGE
    const banner = createPageBanner({
      desktopMdAvif: 'https://example.com/banner-md.avif',
      desktopLgAvif: 'https://example.com/banner-lg.avif',
      desktopXlAvif: 'https://example.com/banner-xl.avif',
    });

    const { container } = render(<SEOPreloadBanner banner={banner} device="desktop" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).toBeInTheDocument();
    expect(preloadLink?.getAttribute('href')).toEqual('https://example.com/banner-md.avif');
    expect(preloadLink?.getAttribute('type')).toEqual('image/avif');
    expect(preloadLink?.getAttribute('media')).toEqual('(min-width: 768px)');
    expect(preloadLink?.getAttribute('imagesrcset')).toEqual(
      'https://example.com/banner-md.avif 1024w, https://example.com/banner-lg.avif 1280w, https://example.com/banner-xl.avif 1920w',
    );
    expect(preloadLink?.getAttribute('imagesizes')).toEqual('100vw');
  });

  it('given a mobile device with no mobileSmAvif, preloads nothing', () => {
    // ARRANGE
    const banner = createPageBanner({ mobileSmAvif: null });

    const { container } = render(<SEOPreloadBanner banner={banner} device="mobile" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toBeInTheDocument();
  });

  it('given a desktop device with no desktopMdAvif, preloads nothing', () => {
    // ARRANGE
    const banner = createPageBanner({ desktopMdAvif: null });

    const { container } = render(<SEOPreloadBanner banner={banner} device="desktop" />);

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toBeInTheDocument();
  });

  it('given an unknown device type, preloads nothing', () => {
    // ARRANGE
    const { container } = render(
      <SEOPreloadBanner banner={createPageBanner()} device={'tablet' as any} />,
    );

    // ASSERT
    const preloadLink = container.querySelector('span[rel="preload"]');
    expect(preloadLink).not.toBeInTheDocument();
  });
});
