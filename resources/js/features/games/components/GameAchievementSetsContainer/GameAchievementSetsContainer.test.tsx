import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievementSet, createGame, createGameAchievementSet } from '@/test/factories';

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
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ id: 123 }) }),
      ],
    });

    render(<GameAchievementSetsContainer game={game} />, {
      pageProps: { game, backingGame: game, targetAchievementSetId: 123 },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /display order/i })).toBeVisible();
  });

  it('given the game has achievement sets and there is no target achievement set ID, renders each set component', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ id: 123 }) }),
        createGameAchievementSet({ achievementSet: createAchievementSet({ id: 456 }) }),
      ],
    });

    render(<GameAchievementSetsContainer game={game} />, {
      pageProps: { game, backingGame: game },
    });

    // ASSERT
    expect(screen.getByTestId('game-achievement-sets')).toBeVisible();
    expect(screen.getAllByRole('list').length).toBeGreaterThanOrEqual(2);
  });

  it('given the user changes the sort order, does not crash', async () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ id: 123 }) }),
      ],
    });

    render(<GameAchievementSetsContainer game={game} />, {
      pageProps: { game, backingGame: game, targetAchievementSetId: 123 },
    });

    // ACT
    const sortButton = screen.getByRole('button', { name: /display order/i });
    await userEvent.click(sortButton);
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: 'Display order (last)' }));

    // ASSERT
    const container = screen.getByTestId('game-achievement-sets');
    expect(container).toBeVisible();
  });
});
