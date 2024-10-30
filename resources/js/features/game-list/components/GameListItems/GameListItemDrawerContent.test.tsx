import { createAuthenticatedUser } from '@/common/models';
import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import {
  createGame,
  createGameListEntry,
  createPlayerBadge,
  createPlayerGame,
  createSystem,
} from '@/test/factories';

import { GameListItemDrawerContent } from './GameListItemDrawerContent';

describe('Component: GameListItemDrawerContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameListItemDrawerContent {...createGameListEntry()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible image representing the game', () => {
    // ARRANGE
    render(
      <GameListItemDrawerContent
        {...createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('always shows the game title', () => {
    // ARRANGE
    render(
      <GameListItemDrawerContent
        {...createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('there is always one or more tappable links directly to the game page', () => {
    // ARRANGE
    render(
      <GameListItemDrawerContent
        {...createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
      />,
    );

    // ASSERT
    expect(
      screen.getAllByRole('link', { name: /sonic the hedgehog/i }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('the non-shortened game system name is always shown', () => {
    // ARRANGE
    const game = createGame({
      title: 'Sonic the Hedgehog',
      system: createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' }),
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    expect(screen.getByText(/sega genesis/i)).toBeVisible();
    expect(screen.getByText(/mega drive/i)).toBeVisible();
  });

  it('given there is no system to display, still renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      title: 'Sonic the Hedgehog',
      system: undefined,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('given a game has a release date, displays it', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: new Date('1987-05-05').toISOString(),
      releasedAtGranularity: 'day',
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const releaseDateEl = screen.getByRole('listitem', { name: /release date/i });

    expect(releaseDateEl).toBeVisible();
    expect(releaseDateEl).toHaveTextContent(/release date/i);
    expect(releaseDateEl).toHaveTextContent('May 5, 1987');
  });

  it("given a game's release date is unknown, renders a fallback label", () => {
    // ARRANGE
    const game = createGame({
      releasedAt: null,
      releasedAtGranularity: 'day',
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const releaseDateEl = screen.getByRole('listitem', { name: /release date/i });

    expect(releaseDateEl).toBeVisible();
    expect(releaseDateEl).toHaveTextContent(/release date/i);
    expect(releaseDateEl).toHaveTextContent('unknown');
  });

  it('given a game has achievements published, displays the count', () => {
    // ARRANGE
    const game = createGame({
      achievementsPublished: 123,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const achievementsPublishedEl = screen.getByRole('listitem', { name: /achievements/i });

    expect(achievementsPublishedEl).toBeVisible();
    expect(achievementsPublishedEl).toHaveTextContent(/achievements/i);
    expect(achievementsPublishedEl).toHaveTextContent('123');
  });

  it('given a game has no achievements published, displays zero', () => {
    // ARRANGE
    const game = createGame({
      achievementsPublished: undefined,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const achievementsPublishedEl = screen.getByRole('listitem', { name: /achievements/i });

    expect(achievementsPublishedEl).toBeVisible();
    expect(achievementsPublishedEl).toHaveTextContent(/achievements/i);
    expect(achievementsPublishedEl).toHaveTextContent('0');
  });

  it('given a game has points, displays the number of points', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: 400,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('100 (400)');
  });

  it('given a game has no weighted points, displays zero for weighted points', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: undefined,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('100 (0)');
  });

  it('given a game has an unknown number of points, displays zero', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: undefined,
      pointsWeighted: undefined,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('0');
  });

  it('given a game has points, displays the rarity', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: 400,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const rarityEl = screen.getByRole('listitem', { name: /rarity/i });

    expect(rarityEl).toBeVisible();
    expect(rarityEl).toHaveTextContent(/rarity/i);
    expect(rarityEl).toHaveTextContent('×4.00');
  });

  it('given a game has an unknown number of points, displays a fallback label for rarity', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: undefined,
      pointsWeighted: 400,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const rarityEl = screen.getByRole('listitem', { name: /rarity/i });

    expect(rarityEl).toBeVisible();
    expect(rarityEl).toHaveTextContent(/rarity/i);
    expect(rarityEl).toHaveTextContent(/none/i);
  });

  it('given a game has a player count, displays the player count', () => {
    // ARRANGE
    const game = createGame({
      playersTotal: 12345,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const playersEl = screen.getByRole('listitem', { name: /players/i });

    expect(playersEl).toBeVisible();
    expect(playersEl).toHaveTextContent(/players/i);
    expect(playersEl).toHaveTextContent('12,345');
  });

  it('given a game has an unknown number of players, falls back to zero', () => {
    // ARRANGE
    const game = createGame({
      playersTotal: undefined,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game })} />);

    // ASSERT
    const playersEl = screen.getByRole('listitem', { name: /players/i });

    expect(playersEl).toBeVisible();
    expect(playersEl).toHaveTextContent(/players/i);
    expect(playersEl).toHaveTextContent('0');
  });

  it('given the user is unauthenticated, does not display a progress row', () => {
    // ARRANGE
    render(<GameListItemDrawerContent {...createGameListEntry()} />, {
      pageProps: { auth: null },
    });

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /progress/i })).not.toBeInTheDocument();
  });

  it('given the user is authenticated, displays a progress row', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 0,
      highestAward: null,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game, playerGame })} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const progressEl = screen.getByRole('listitem', { name: /progress/i });

    expect(progressEl).toBeVisible();
    expect(progressEl).toHaveTextContent(/progress/i);
    expect(progressEl).toHaveTextContent(/none/i);
  });

  it('given the user has progress, displays a percentage of their progress in the progress row', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 30,
      highestAward: null,
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game, playerGame })} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const progressEl = screen.getByRole('listitem', { name: /progress/i });

    expect(progressEl).toBeVisible();
    expect(progressEl).toHaveTextContent(/progress/i);
    expect(progressEl).toHaveTextContent(/30%/i);
  });

  it('given the user has an award, displays an award label in the progress row', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 30,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
      }),
    });

    render(<GameListItemDrawerContent {...createGameListEntry({ game, playerGame })} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const progressEl = screen.getByRole('listitem', { name: /progress/i });

    expect(progressEl).toBeVisible();
    expect(progressEl).toHaveTextContent(/progress/i);
    expect(progressEl).toHaveTextContent(/30%/i);
    expect(progressEl).toHaveTextContent(/mastered/i);
  });
});
