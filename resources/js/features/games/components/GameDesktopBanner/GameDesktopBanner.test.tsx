import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createPageBanner, createSystem } from '@/test/factories';

import { GameDesktopBanner } from './GameDesktopBanner';

describe('Component: GameDesktopBanner', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
        game: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Sonic the Hedgehog');
  });

  it('displays the game badge image', () => {
    // ARRANGE
    const game = createGame({
      title: 'Sonic the Hedgehog',
      badgeUrl: 'https://example.com/badge.png',
    });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const badge = screen.getByAltText('Sonic the Hedgehog');
    expect(badge).toHaveAttribute('src', 'https://example.com/badge.png');
  });

  it('displays the system name and icon', () => {
    // ARRANGE
    const system = createSystem({
      name: 'Genesis/Mega Drive',
      nameShort: 'MD',
      iconUrl: 'https://example.com/md-icon.png',
    });
    const game = createGame({ system });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByText('Genesis/Mega Drive')).toBeVisible();
    expect(screen.getByAltText('MD')).toHaveAttribute('src', 'https://example.com/md-icon.png');
  });

  it('uses banner edge colors in the background gradient', () => {
    // ARRANGE
    const banner = createPageBanner({
      leftEdgeColor: '#ff0000',
      rightEdgeColor: '#0000ff',
    });

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
        game: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl.style.background).toContain('#ff0000');
    expect(bannerEl.style.background).toContain('#0000ff');
  });

  it('falls back to default edge color when not provided', () => {
    // ARRANGE
    const banner = createPageBanner({
      leftEdgeColor: null,
      rightEdgeColor: null,
    });

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
        game: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl.style.background).toContain('#0a0a0a');
  });

  it('given viewing a subset, shows the subset indicator on the want to play toggle', () => {
    // ARRANGE
    const backingGame = createGame({ id: 1 });
    const game = createGame({ id: 2 }); // different id means viewing a subset

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        backingGame,
        game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/subset/i)).toBeVisible();
  });

  it('given not viewing a subset, does not show the subset indicator', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game, // !! same id as backingGame
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/subset/i)).not.toBeInTheDocument();
  });

  it('renders the blurred background image layer', () => {
    // ARRANGE
    const banner = createPageBanner({
      desktopMdWebp: 'https://example.com/banner-md.webp',
      desktopMdAvif: 'https://example.com/banner-md.avif',
    });
    const game = createGame({ imageIngameUrl: 'https://example.com/ingame.jpg' });

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const blurredImg = screen.getByTestId('blurred-backdrop');
    expect(blurredImg).toBeInTheDocument();
    expect(blurredImg).toHaveStyle({ filter: 'blur(15px)' });
  });

  it('given banner sources are null, does not render the blurred backdrop', () => {
    // ARRANGE
    const banner = createPageBanner({
      desktopMdWebp: null,
      desktopMdAvif: null,
    });
    const game = createGame({ imageIngameUrl: 'https://example.com/ingame.jpg' });

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('blurred-backdrop')).not.toBeInTheDocument();
  });

  it('given a fallback banner, uses a fixed compact height regardless of preference', () => {
    // ARRANGE
    const banner = createPageBanner({ isFallback: true });
    const game = createGame();

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'expanded',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');

    expect(bannerEl).toHaveClass('lg:h-[212px]');
    expect(bannerEl).not.toHaveClass('lg:!h-[474px]');
  });

  it('given a fallback banner, does not render the expand/collapse button', () => {
    // ARRANGE
    const banner = createPageBanner({ isFallback: true });
    const game = createGame();

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /expand banner/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /collapse banner/i })).not.toBeInTheDocument();
  });

  it('given a fallback banner, still renders the blurred backdrop and banner image', () => {
    // ARRANGE
    const banner = createPageBanner({
      desktopMdWebp: '/assets/images/banner/fallback-desktop-md.webp',
      isFallback: true,
    });
    const game = createGame();

    render(<GameDesktopBanner banner={banner} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const blurredBackdrop = screen.getByTestId('blurred-backdrop');
    expect(blurredBackdrop).toHaveAttribute(
      'src',
      '/assets/images/banner/fallback-desktop-md.webp',
    );
  });

  it('given a title longer than 30 characters, applies smaller text size for mobile', () => {
    // ARRANGE
    const longTitle = 'This Is A Very Long Game Title That Exceeds Thirty';
    const game = createGame({ title: longTitle });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const heading = screen.getByRole('heading', { level: 1 });
    expect(heading).toHaveClass('!text-base');
    expect(heading).toHaveClass('md:!text-2xl');
  });

  it('given a title longer than 50 characters, applies even smaller text size and line clamping', () => {
    // ARRANGE
    const veryLongTitle =
      'This Is An Extremely Long Game Title That Definitely Exceeds Fifty Characters In Length';
    const game = createGame({ title: veryLongTitle });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const heading = screen.getByRole('heading', { level: 1 });
    expect(heading).toHaveClass('!text-sm');
    expect(heading).toHaveClass('md:!text-xl');
    expect(heading).toHaveClass('line-clamp-2');
  });

  it('given the "normal" banner preference, has an accessible "expand" label on the toggle button', () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'normal',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /expand banner/i })).toBeInTheDocument();
  });

  it('given the "compact" banner preference, has an accessible "expand" label on the toggle button', () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'compact',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /expand banner/i })).toBeInTheDocument();
  });

  it('given the "expanded" banner preference, has an accessible "collapse" label on the toggle button', () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'expanded',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /collapse banner/i })).toBeInTheDocument();
  });

  it('given the "compact" banner preference, applies the compact height class', () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'compact',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl).toHaveClass('lg:!h-[212px]');
  });

  it('given the "expanded" banner preference, applies the expanded height class', () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'expanded',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl).toHaveClass('lg:!h-[474px]');
  });

  it('given the toggle button is clicked, cycles through banner preferences', async () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'normal',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    const bannerEl = screen.getByTestId('desktop-banner');

    // ASSERT
    // ... normal -> compact ...
    await userEvent.click(screen.getByRole('button', { name: /expand banner/i }));
    expect(bannerEl).toHaveClass('lg:!h-[212px]');

    // ASSERT
    // ... compact -> expanded ...
    await userEvent.click(screen.getByRole('button', { name: /expand banner/i }));
    expect(bannerEl).toHaveClass('lg:!h-[474px]');

    // ASSERT
    // ... expanded -> normal ...
    await userEvent.click(screen.getByRole('button', { name: /collapse banner/i }));

    expect(bannerEl).not.toHaveClass('lg:!h-[212px]');
    expect(bannerEl).not.toHaveClass('lg:!h-[474px]');
  });

  it('given the toggle button is hovered, changes the border color', async () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'normal',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /expand banner/i }));

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl).toHaveClass('border-neutral-500');
  });

  it('given the user can manage games, shows the manage chip', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: { manageGames: true },
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
  });

  it('given the user cannot manage games, does not show the manage chip', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        can: { manageGames: false },
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });

  it('given the toggle button is unhovered, reverts the border color', async () => {
    // ARRANGE
    const game = createGame();

    render(<GameDesktopBanner banner={createPageBanner()} />, {
      pageProps: {
        game,
        backingGame: game,
        bannerPreference: 'normal',
        can: {},
        isOnWantToPlayList: false,
      },
    });

    const toggleButton = screen.getByRole('button', { name: /expand banner/i });

    // ACT
    await userEvent.hover(toggleButton);
    await userEvent.unhover(toggleButton);

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl).not.toHaveClass('border-neutral-500');
  });
});
