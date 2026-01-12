/* eslint-disable testing-library/no-container */

import { act, render } from '@/test';
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

  it('renders the picture element with AVIF and WebP sources', () => {
    // ARRANGE
    const banner = createPageBanner({
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
    expect(sources).toHaveLength(2);

    // ... AVIF source ...
    expect(sources?.[0]).toHaveAttribute('type', 'image/avif');
    expect(sources?.[0]).toHaveAttribute(
      'srcset',
      'https://example.com/banner-md.avif 1024w, https://example.com/banner-lg.avif 1280w, https://example.com/banner-xl.avif 1920w',
    );

    // ... WebP source ...
    expect(sources?.[1]).toHaveAttribute('type', 'image/webp');
    expect(sources?.[1]).toHaveAttribute(
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
    const { container } = render(<GameDesktopBannerImage banner={createPageBanner()} />, {
      pageProps: { game: createGame() },
    });

    // ASSERT
    const gradients = container.querySelectorAll('.bg-gradient-to-b, .bg-gradient-to-t');
    expect(gradients.length).toBeGreaterThanOrEqual(2);
  });
});
