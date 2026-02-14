import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createGame,
  createGameActivitySnapshot,
  createHomePageProps,
  createRaEvent,
} from '@/test/factories';

import { GameActivity } from './GameActivity';

describe('Component: GameActivity', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameActivity />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('returns null when both trending and popular games are empty', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots: [], popularGameSnapshots: [] }),
    });

    // ASSERT
    expect(screen.queryByRole('radio')).not.toBeInTheDocument();
  });

  it('given trending mode is selected and the user clicks the Trending toggle button, does not unselect trending mode', async () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Trending Game' }),
        trendingReason: 'new-set',
      }),
    ];
    const popularGameSnapshots = [
      createGameActivitySnapshot({ game: createGame({ title: 'Popular Game' }), playerCount: 100 }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots }),
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle trending/i }));

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle trending/i })).toBeChecked();
    expect(screen.getByText(/trending right now/i)).toBeVisible();
  });

  it('displays trending games with trending reasons by default', () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Super Mario Bros' }),
        trendingReason: 'new-set',
      }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots: [] }),
    });

    // ASSERT
    expect(screen.getByText(/super mario bros/i)).toBeVisible();
    expect(screen.getByText(/new set driving interest/i)).toBeVisible();
    expect(screen.getByText(/trending right now/i)).toBeVisible();
  });

  it('given there is no trending data, defaults to the popular tab', () => {
    // ARRANGE
    const popularGameSnapshots = [
      createGameActivitySnapshot({ game: createGame({ title: 'Popular Game' }), playerCount: 100 }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots: [], popularGameSnapshots }),
    });

    // ASSERT
    expect(screen.getByText(/popular right now/i)).toBeVisible();
    expect(screen.getByText(/popular game/i)).toBeVisible();
  });

  it('given there is no trending data, disables the trending tab', () => {
    // ARRANGE
    const popularGameSnapshots = [
      createGameActivitySnapshot({ game: createGame({ title: 'Popular Game' }), playerCount: 100 }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots: [], popularGameSnapshots }),
    });

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle trending/i })).toBeDisabled();
  });

  it('given there is no popular data, disables the popular tab', () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Trending Game' }),
        trendingReason: 'new-set',
      }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots: [] }),
    });

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle popular/i })).toBeDisabled();
  });

  it('can toggle to popular view', async () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Trending Game' }),
        trendingReason: 'new-set',
      }),
    ];
    const popularGameSnapshots = [
      createGameActivitySnapshot({ game: createGame({ title: 'Popular Game' }), playerCount: 500 }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots }),
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle popular/i }));

    // ASSERT
    expect(screen.getByText(/popular right now/i)).toBeVisible();
    expect(screen.getByText(/popular game/i)).toBeVisible();
    expect(screen.getByText(/500 players/i)).toBeVisible();
  });

  it('can toggle back to trending view', async () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Trending Game' }),
        trendingReason: 'gaining-traction',
      }),
    ];
    const popularGameSnapshots = [
      createGameActivitySnapshot({ game: createGame({ title: 'Popular Game' }), playerCount: 500 }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots }),
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle popular/i }));
    await userEvent.click(screen.getByRole('radio', { name: /toggle trending/i }));

    // ASSERT
    expect(screen.getByText(/trending right now/i)).toBeVisible();
    expect(screen.getByText(/trending game/i)).toBeVisible();
    expect(screen.getByText(/rising in popularity/i)).toBeVisible();
  });

  it('given a trending game has event data, shows the event title as a link instead of the trending reason', () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Dark Cloud' }),
        trendingReason: 'more-players',
        event: createRaEvent({
          id: 196,
          legacyGame: createGame({ title: 'Achievement of the Week 2024' }),
        }),
      }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots: [] }),
    });

    // ASSERT
    const eventLinkEl = screen.getByRole('link', { name: /achievement of the week 2024/i });
    expect(eventLinkEl).toBeVisible();
    expect(eventLinkEl).toHaveAttribute('href', expect.stringContaining('event.show'));

    // ... the generic reason should be replaced by the event link ...
    expect(screen.queryByText(/more players than usual/i)).not.toBeInTheDocument();
  });

  it('given a trending game has no event data, does not render an event link', () => {
    // ARRANGE
    const trendingGameSnapshots = [
      createGameActivitySnapshot({
        game: createGame({ title: 'Super Mario Bros' }),
        trendingReason: 'new-set',
        event: null,
      }),
    ];

    render<App.Http.Data.HomePageProps>(<GameActivity />, {
      pageProps: createHomePageProps({ trendingGameSnapshots, popularGameSnapshots: [] }),
    });

    // ASSERT
    expect(screen.getByText(/new set driving interest/i)).toBeVisible();

    const allLinks = screen.getAllByRole('link');
    const eventLinks = allLinks.filter((link) => link.getAttribute('href')?.includes('event.show'));
    expect(eventLinks).toHaveLength(0);
  });
});
