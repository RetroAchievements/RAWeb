import { render, screen } from '@/test';
import { createGame, createPlayerGame } from '@/test/factories';

import { GamesListItem } from './GamesListItem';

describe('Component: GamesListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game' });

    const { container } = render(<GamesListItem game={game} playerGame={null} index={0} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a game, displays its title and system', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game' });

    render(<GamesListItem game={game} playerGame={null} index={0} />);

    // ASSERT
    expect(screen.getByText('Test Game')).toBeVisible();
    expect(screen.getByText(game.system!.nameShort!)).toBeVisible();
  });

  it('given no player game information, displays 0 of X achievements', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });

    render(<GamesListItem game={game} playerGame={null} index={0} />);

    // ASSERT
    expect(screen.getByText('0 of 8')).toBeVisible();
  });

  it('given player game information, displays N of X achievements', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 1,
      achievementsUnlockedHardcore: 2,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('2 of 8')).toBeVisible();
  });

  it('given no player game information, does not display award information', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game' });

    render(<GamesListItem game={game} playerGame={null} index={0} />);

    // ASSERT
    expect(screen.queryByText(/Mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten/i)).not.toBeInTheDocument(); // also covers Beaten (softcore)
  });

  it('given player game mastery, displays mastery timestamp', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: '2024-01-06 14:32:11',
      completedAt: null,
      beatenHardcoreAt: null,
      beatenAt: null,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Mastered Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten/i)).not.toBeInTheDocument(); // also covers Beaten (softcore)
  });

  it('given player game completion, displays completion timestamp', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: null,
      completedAt: '2024-01-06 14:32:11',
      beatenHardcoreAt: null,
      beatenAt: null,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Completed Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.queryByText(/Mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten/i)).not.toBeInTheDocument(); // also covers Beaten (softcore)
  });

  it('given player game beat, displays beaten timestamp', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: null,
      completedAt: null,
      beatenHardcoreAt: '2024-01-06 14:32:11',
      beatenAt: null,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Beaten Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.queryByText(/Mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten (softcore)/i)).not.toBeInTheDocument();
  });

  it('given player game softcore beat, displays beaten timestamp', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: null,
      completedAt: null,
      beatenHardcoreAt: null,
      beatenAt: '2024-01-06 14:32:11',
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Beaten (softcore) Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.queryByText(/Mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
  });

  it('ignores empty strings in date fields', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: '',
      completedAt: '',
      beatenHardcoreAt: '',
      beatenAt: '',
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.queryByText(/Mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten/i)).not.toBeInTheDocument(); // also covers Beaten (softcore)
  });

  it('given player game mastery and completion, only displays mastery timestamp', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 4,
      completedHardcoreAt: '2024-01-06 14:32:11',
      completedAt: '2024-01-05 18:40:03',
      beatenHardcoreAt: null,
      beatenAt: null,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Mastered Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Beaten/i)).not.toBeInTheDocument(); // also covers Beaten (softcore)
  });

  it('given player game mastery and beat, displays mastery and beat timestamps', () => {
    // ARRANGE
    const game = createGame({ title: 'Test Game', achievementsPublished: 8 });
    const playerGame = createPlayerGame({
      highestAward: null,
      achievementsUnlockedHardcore: 8,
      completedHardcoreAt: '2024-01-06 14:32:11',
      completedAt: null,
      beatenHardcoreAt: '2024-01-05 18:40:03',
      beatenAt: null,
    });

    render(<GamesListItem game={game} playerGame={playerGame} index={0} />);

    // ASSERT
    expect(screen.getByText('Mastered Jan 6, 2024 2:32 PM')).toBeVisible();
    expect(screen.getByText('Beaten Jan 5, 2024 6:40 PM')).toBeVisible();
    expect(screen.queryByText(/Completed/i)).not.toBeInTheDocument();
  });
});
