import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createGameAchievementSet } from '@/test/factories';

import { GameAchievementSetsContainer } from './GameAchievementSetsContainer';

describe('Component: GameAchievementSetsContainer', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameAchievementSetsContainer game={createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has no achievement sets, shows an empty state', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [],
    });

    render(<GameAchievementSetsContainer game={game} />);

    // ASSERT
    expect(screen.getByText(/aren't any achievements/i)).toBeVisible();
  });

  it('given the game has achievement sets, shows the sort button', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
    });

    render(<GameAchievementSetsContainer game={game} />, { pageProps: { game } });

    // ASSERT
    expect(screen.getByRole('button', { name: /display order/i })).toBeVisible();
  });

  it('given the game has achievement sets, renders each set component', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({ id: 1 }),
        createGameAchievementSet({ id: 2 }),
      ],
    });

    render(<GameAchievementSetsContainer game={game} />, { pageProps: { game } });

    // ASSERT
    expect(screen.getByTestId('game-achievement-sets')).toBeVisible();
    expect(screen.getAllByRole('list').length).toBeGreaterThanOrEqual(2);
  });

  it('given the user changes the sort order, does not crash', async () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
    });

    render(<GameAchievementSetsContainer game={game} />, { pageProps: { game } });

    // ACT
    const sortButton = screen.getByRole('button', { name: /display order/i });
    await userEvent.click(sortButton);
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: 'Display order (last)' }));

    // ASSERT
    const container = screen.getByTestId('game-achievement-sets');
    expect(container).toBeVisible();
  });
});
