/* eslint-disable testing-library/no-container */

import { act, render, screen } from '@/test';
import { createGame, createPageBanner } from '@/test/factories';

import { GameDesktopBannerImage } from './GameDesktopBannerImage';

describe('Component: GameDesktopBannerImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: {
        game: createGame(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the banner has no mobile sources, still renders without crashing', () => {
    // ARRANGE
    const banner = createPageBanner({
      mobileSmAvif: null,
      mobileSmWebp: null,
    });

    const { container } = render(<GameDesktopBannerImage banner={banner} />, {
      pageProps: { game: createGame() },
    });

    // ASSERT
    const sources = container.querySelectorAll('source');

    const mobileAvifSource = sources[0];
    const mobileWebpSource = sources[1];
    expect(mobileAvifSource).not.toHaveAttribute('srcset');
    expect(mobileWebpSource).not.toHaveAttribute('srcset');
  });

  it('renders the picture element with mobile and desktop AVIF/WebP sources', () => {
    // ARRANGE
    const banner = createPageBanner({
      mobileSmAvif: 'https://example.com/banner-mobile.avif',
      mobileSmWebp: 'https://example.com/banner-mobile.webp',
      desktopMdAvif: 'https://example.com/banner-md.avif',
      desktopLgAvif: 'https://example.com/banner-lg.avif',
      desktopXlAvif: 'https://example.com/banner-xl.avif',
      desktopMdWebp: 'https://example.com/banner-md.webp',
      desktopLgWebp: 'https://example.com/banner-lg.webp',
      desktopXlWebp: 'https://example.com/banner-xl.webp',
    });

    const { container } = render(<GameDesktopBannerImage banner={banner} />, {
      pageProps: {
        game: createGame({ imageIngameUrl: 'https://example.com/ingame.jpg' }),
      },
    });

    // ASSERT
    const picture = container.querySelector('picture');
    expect(picture).toBeInTheDocument();

    const sources = picture?.querySelectorAll('source');
    expect(sources).toHaveLength(4);

    // ... mobile AVIF source ...
    expect(sources?.[0]).toHaveAttribute('type', 'image/avif');
    expect(sources?.[0]).toHaveAttribute('media', '(max-width: 767px)');
    expect(sources?.[0]).toHaveAttribute('srcset', 'https://example.com/banner-mobile.avif');

    // ... mobile WebP source ...
    expect(sources?.[1]).toHaveAttribute('type', 'image/webp');
    expect(sources?.[1]).toHaveAttribute('media', '(max-width: 767px)');
    expect(sources?.[1]).toHaveAttribute('srcset', 'https://example.com/banner-mobile.webp');

    // ... desktop AVIF source ...
    expect(sources?.[2]).toHaveAttribute('type', 'image/avif');
    expect(sources?.[2]).toHaveAttribute(
      'srcset',
      'https://example.com/banner-md.avif 1024w, https://example.com/banner-lg.avif 1280w, https://example.com/banner-xl.avif 1920w',
    );

    // ... desktop WebP source ...
    expect(sources?.[3]).toHaveAttribute('type', 'image/webp');
    expect(sources?.[3]).toHaveAttribute(
      'srcset',
      'https://example.com/banner-md.webp 1024w, https://example.com/banner-lg.webp 1280w, https://example.com/banner-xl.webp 1920w',
    );
  });

  it('as an edge case, uses the ingame screenshot as the fallback img src', () => {
    // ARRANGE
    const banner = createPageBanner();
    const game = createGame({ imageIngameUrl: 'https://example.com/ingame.jpg' });

    const { container } = render(<GameDesktopBannerImage banner={banner} />, {
      pageProps: { game },
    });

    // ASSERT
    const img = container.querySelector('picture img');
    expect(img).toHaveAttribute('src', 'https://example.com/ingame.jpg');
    expect(img).toHaveAttribute('fetchpriority', 'high');
    expect(img).toHaveAttribute('loading', 'eager');
  });

  it('initially renders the picture with opacity-0', () => {
    // ARRANGE
    const { container } = render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: { game: createGame() },
    });

    // ASSERT
    const picture = container.querySelector('picture');
    expect(picture).toHaveClass('opacity-0');
  });

  it('given the image loads, transitions the picture to opacity-100', () => {
    // ARRANGE
    const { container } = render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: { game: createGame() },
    });

    const picture = container.querySelector('picture');
    const img = picture?.querySelector('img');

    // ... initially invisible ...
    expect(picture).toHaveClass('opacity-0');

    // ACT
    act(() => {
      img?.dispatchEvent(new Event('load'));
    });

    // ASSERT
    expect(picture).toHaveClass('opacity-100');
  });

  it('given the img element is already complete when ref is attached, sets loaded state immediately', () => {
    // ARRANGE
    Object.defineProperty(HTMLImageElement.prototype, 'complete', {
      configurable: true,
      get: () => true,
    });

    const { container } = render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: { game: createGame() },
    });

    // ASSERT
    const picture = container.querySelector('picture');
    expect(picture).toHaveClass('opacity-100');

    // ... cleanup to avoid memory leaks ...
    delete (HTMLImageElement.prototype as any).complete;
  });

  it('renders gradient overlays for navbar blending and text readability', () => {
    // ARRANGE
    render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: { game: createGame() },
    });

    // ASSERT
    expect(screen.getByTestId('top-gradient-dark')).toBeInTheDocument();
    expect(screen.getByTestId('top-gradient-light')).toBeInTheDocument();
    expect(screen.getByTestId('bottom-gradient-dark')).toBeInTheDocument();
    expect(screen.getByTestId('bottom-gradient-light')).toBeInTheDocument();
  });
});
