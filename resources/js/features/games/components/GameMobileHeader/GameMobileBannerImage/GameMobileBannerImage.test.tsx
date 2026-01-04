/* eslint-disable testing-library/no-container */

import { act, render, screen } from '@/test';
import { createGame, createGameBanner, createSystem } from '@/test/factories';

import { GameMobileBannerImage } from './GameMobileBannerImage';

describe('Component: GameMobileBannerImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameMobileBannerImage />, {
      pageProps: {
        game: createGame(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has a banner, renders the picture element', () => {
    // ARRANGE
    const game = createGame({
      banner: createGameBanner({
        mobileSmWebp: 'https://example.com/banner.webp',
      }),
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const picture = container.querySelector('picture');
    expect(picture).toBeInTheDocument();
  });

  it('given the game has a banner, renders the blurred placeholder image', () => {
    // ARRANGE
    const game = createGame({
      banner: createGameBanner({
        mobileSmWebp: 'https://example.com/banner.webp',
        mobilePlaceholder: 'data:image/jpeg;base64,placeholder',
      }),
    });

    render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const placeholderImg = screen.getByAltText(/game banner/i);
    expect(placeholderImg).toBeVisible();
    expect(placeholderImg).toHaveAttribute('src', 'data:image/jpeg;base64,placeholder');

    expect(placeholderImg).toHaveClass('opacity-100'); // initially visible
  });

  it('given the game has no banner, renders the fallback img with the ingame screenshot', () => {
    // ARRANGE
    const game = createGame({
      banner: undefined, // !!
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const fallbackImg = container.querySelector('img[src="https://example.com/ingame.jpg"]');

    expect(fallbackImg).toBeInTheDocument();
    expect(fallbackImg).toHaveAttribute('fetchpriority', 'high');
    expect(fallbackImg).toHaveAttribute('loading', 'eager');
  });

  it('given the game is for the Nintendo DS, applies special positioning styles to the fallback image', () => {
    // ARRANGE
    const system = createSystem({ id: 18 }); // !! Nintendo DS system id
    const game = createGame({
      system,
      banner: undefined,
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const fallbackImg = container.querySelector('img[src="https://example.com/ingame.jpg"]');
    expect(fallbackImg).toBeInTheDocument();
    expect((fallbackImg as any)?.style.objectPosition).toEqual('center 0%');
    expect((fallbackImg as any)?.style.objectFit).toEqual('none');
    expect((fallbackImg as any)?.style.scale).toEqual('2');
  });

  it('given the game is not for Nintendo DS, applies standard positioning styles to the fallback image', () => {
    // ARRANGE
    const system = createSystem({ id: 1 }); // !! some other system id, not Nintendo DS
    const game = createGame({
      system,
      banner: undefined,
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const fallbackImg = container.querySelector('img[src="https://example.com/ingame.jpg"]');
    expect(fallbackImg).toBeInTheDocument();
    expect((fallbackImg as any)?.style.objectPosition).toEqual('center');
    expect((fallbackImg as any)?.style.objectFit).toEqual('cover');
  });

  it('given the game is for Nintendo DS and a banner is used, applies special positioning to the fallback img element', () => {
    // ARRANGE
    const system = createSystem({ id: 18 }); // !! Nintendo DS system id
    const game = createGame({
      system,
      banner: createGameBanner({
        mobileSmWebp: 'https://example.com/banner.webp',
        mobilePlaceholder: null,
        mobileSmAvif: null,
      }),
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const img = container.querySelector('picture img');
    expect(img).toHaveStyle({
      objectPosition: 'center 0%',
    });
  });

  it('given the image loads, the placeholder becomes invisible and the full image becomes visible', () => {
    // ARRANGE
    const game = createGame({
      banner: createGameBanner({
        mobileSmWebp: 'https://example.com/banner.webp',
        mobilePlaceholder: 'data:image/jpeg;base64,placeholder',
      }),
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    const placeholderImg = screen.getByAltText(/game banner/i);
    const picture = container.querySelector('picture');
    const fullImg = picture?.querySelector('img');

    // ... initially, the placeholder is visible and the picture is invisible ...
    expect(placeholderImg).toHaveClass('opacity-100');
    expect(picture).toHaveClass('opacity-0');

    // ACT
    // ... simulate the full image load ...
    act(() => {
      if (fullImg) {
        fullImg.dispatchEvent(new Event('load'));
      }
    });

    // ASSERT
    expect(placeholderImg).toHaveClass('opacity-0'); // placeholder hidden.
    expect(picture).toHaveClass('opacity-100'); // full image visible
  });

  it('given the img element is already complete when ref is attached, sets loaded state immediately', () => {
    // ARRANGE
    const game = createGame({
      banner: createGameBanner({
        mobileSmWebp: 'https://example.com/banner.webp',
        mobilePlaceholder: 'data:image/jpeg;base64,placeholder',
      }),
      imageIngameUrl: 'https://example.com/ingame.jpg',
    });

    // ... mock the image as already complete ...
    Object.defineProperty(HTMLImageElement.prototype, 'complete', {
      configurable: true,
      get: () => true, // !! image is already loaded
    });

    const { container } = render(<GameMobileBannerImage />, {
      pageProps: { game },
    });

    // ASSERT
    const placeholderImg = screen.getByAltText(/game banner/i);
    const picture = container.querySelector('picture');

    expect(placeholderImg).toHaveClass('opacity-0'); // immediately hidden
    expect(picture).toHaveClass('opacity-100'); // immediately visible

    // ... don't cause a memory leak in vitest ...
    delete (HTMLImageElement.prototype as any).complete;
  });
});
