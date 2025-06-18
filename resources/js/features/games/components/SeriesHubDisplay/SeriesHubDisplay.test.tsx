import { render, screen } from '@/test';
import { createGame, createGameSet, createSeriesHub } from '@/test/factories';

import { SeriesHubDisplay } from './SeriesHubDisplay';

describe('Component: SeriesHubDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        badgeUrl: 'https://example.com/badge.png',
        title: '[Series - Super Mario]',
      }),
      totalGameCount: 5,
      achievementsPublished: 100,
      pointsTotal: 1000,
      topGames: [],
    });

    const { container } = render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a series hub, displays the title and associated metadata', () => {
    // ARRANGE
    const game = createGame();
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        badgeUrl: 'https://example.com/badge.png',
        title: '[Series - Super Mario]',
      }),
      totalGameCount: 15,
      achievementsPublished: 500,
      pointsTotal: 5000,
      topGames: [],
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByText(/series/i)).toBeVisible();
    expect(screen.getByText(/super mario/i)).toBeVisible();
    expect(screen.getByText('15')).toBeVisible();
    expect(screen.getByText('games')).toBeVisible();
    expect(screen.getByText('500')).toBeVisible();
    expect(screen.getByText('achievements')).toBeVisible();
    expect(screen.getByText('5,000')).toBeVisible();
    expect(screen.getByText('points')).toBeVisible();
  });

  it('given there are at least 2 top games, renders them', () => {
    // ARRANGE
    const currentGame = createGame({ id: 2 });
    const topGames = [
      createGame({ id: 1, title: 'Game 1' }),
      createGame({ id: 2, title: 'Game 2' }),
      createGame({ id: 3, title: 'Game 3' }),
    ];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    const gameAvatars = screen.getAllByRole('img');
    expect(gameAvatars).toHaveLength(4); // !! hub badge + 3 game avatars
  });

  it('given the current game is in the top games list, highlights it', () => {
    // ARRANGE
    const currentGame = createGame({ id: 2 });
    const topGames = [
      createGame({ id: 1, title: 'Game 1' }),
      createGame({ id: 2, title: 'Game 2' }),
      createGame({ id: 3, title: 'Game 3' }),
    ];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
  });

  it('given there are less than 2 top games, does not render the top games section', () => {
    // ARRANGE
    const game = createGame();
    const seriesHub = createSeriesHub({
      topGames: [createGame()],
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    // ... only the hub badge image should be present ...
    const images = screen.getAllByRole('img');
    expect(images).toHaveLength(1);
    expect(screen.queryByTestId('0-no-highlight')).not.toBeInTheDocument();
  });

  it('given there are additional games beyond the top games, renders the count', () => {
    // ARRANGE
    const game = createGame();
    const topGames = [createGame(), createGame()];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 10,
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByText('+10')).toBeVisible();
  });

  it('given there are no additional games, does not render the additional count', () => {
    // ARRANGE
    const game = createGame();
    const topGames = [createGame(), createGame()];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.queryByText('+0')).not.toBeInTheDocument();
  });

  it('given all 5 top games are available, renders all of them', () => {
    // ARRANGE
    const game = createGame();
    const topGames = [
      createGame({ id: 1 }),
      createGame({ id: 2 }),
      createGame({ id: 3 }),
      createGame({ id: 4 }),
      createGame({ id: 5 }),
    ];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    const gameAvatars = screen.getAllByRole('img');
    expect(gameAvatars).toHaveLength(6); // !! hub badge + 5 game avatars

    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-no-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
    expect(screen.getByTestId('3-no-highlight')).toBeVisible();
    expect(screen.getByTestId('4-no-highlight')).toBeVisible();
  });

  it('given only 3 top games are available, renders correctly', () => {
    // ARRANGE
    const game = createGame();
    const topGames = [createGame({ id: 1 }), createGame({ id: 2 }), createGame({ id: 3 })];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    const gameAvatars = screen.getAllByRole('img');
    expect(gameAvatars).toHaveLength(4); // !! hub badge + 3 game avatars.

    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-no-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
    expect(screen.queryByTestId('3-no-highlight')).not.toBeInTheDocument();
    expect(screen.queryByTestId('4-no-highlight')).not.toBeInTheDocument();
  });

  it('given the current game appears multiple times in the top games, highlights all instances', () => {
    // ARRANGE
    const currentGame = createGame({ id: 1 });
    const topGames = [createGame({ id: 1 }), createGame({ id: 1 }), createGame({ id: 2 })];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByTestId('0-highlight')).toBeVisible();
    expect(screen.getByTestId('1-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
  });

  it('given a hub with a prefixed title, cleans it properly', () => {
    // ARRANGE
    const game = createGame();
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        title: '[Series - Super Mario]',
      }),
    });

    render(<SeriesHubDisplay game={game} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.queryByText(/\[series/i)).not.toBeInTheDocument();
    expect(screen.getByText(/super mario/i)).toBeVisible();
  });

  it('given the current game is at index 2, highlights it correctly', () => {
    // ARRANGE
    const currentGame = createGame({ id: 3 });
    const topGames = [createGame({ id: 1 }), createGame({ id: 2 }), createGame({ id: 3 })];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-no-highlight')).toBeVisible();
    expect(screen.getByTestId('2-highlight')).toBeVisible();
  });

  it('given the current game is at index 3, highlights it correctly', () => {
    // ARRANGE
    const currentGame = createGame({ id: 4 });
    const topGames = [
      createGame({ id: 1 }),
      createGame({ id: 2 }),
      createGame({ id: 3 }),
      createGame({ id: 4 }),
    ];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-no-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
    expect(screen.getByTestId('3-highlight')).toBeVisible();
  });

  it('given the current game is at index 4, highlights it correctly', () => {
    // ARRANGE
    const currentGame = createGame({ id: 5 });
    const topGames = [
      createGame({ id: 1 }),
      createGame({ id: 2 }),
      createGame({ id: 3 }),
      createGame({ id: 4 }),
      createGame({ id: 5 }),
    ];
    const seriesHub = createSeriesHub({
      topGames,
      additionalGameCount: 0,
    });

    render(<SeriesHubDisplay game={currentGame} seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByTestId('0-no-highlight')).toBeVisible();
    expect(screen.getByTestId('1-no-highlight')).toBeVisible();
    expect(screen.getByTestId('2-no-highlight')).toBeVisible();
    expect(screen.getByTestId('3-no-highlight')).toBeVisible();
    expect(screen.getByTestId('4-highlight')).toBeVisible();
  });
});
