import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievementSet, createGame, createGameAchievementSet } from '@/test/factories';

import { PlaytimeStatistics } from './PlaytimeStatistics';

describe('Component: PlaytimeStatistics', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    const { container } = render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the playtime stats heading', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByText(/playtime stats/i)).toBeVisible();
  });

  it('shows hardcore mode selected by default', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).toBeChecked();
  });

  it('given hardcore mode is selected and the user clicks the Hardcore toggle button, does not unselect hardcore mode', async () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle hardcore/i }));

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).toBeChecked();
  });

  it('given the user clicks the softcore toggle, switches to softcore mode', async () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle softcore/i }));

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle softcore/i })).toBeChecked();
  });

  it('displays all three playtime rows when the backing game matches the current game', () => {
    // ARRANGE
    const game = createGame({
      id: 123,
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByText(/unlocked an achievement/i)).toBeVisible();
    expect(screen.getByText(/beat the game/i)).toBeVisible();
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('given the backing game does not match the current game, does not show the beat the game row', () => {
    // ARRANGE
    const backingGame = createGame({ id: 123 });
    const game = createGame({
      id: 456,
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByText(/unlocked an achievement/i)).toBeVisible();
    expect(screen.queryByText(/beat the game/i)).not.toBeInTheDocument();
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('given the back-end returned multiple visible achievement sets, displays nothing', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet(), createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('playtime-statistics')).not.toBeInTheDocument();
  });

  it('given the back-end returned no achievement sets, displays nothing', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: undefined,
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('playtime-statistics')).not.toBeInTheDocument();
  });

  it('shows hardcore statistics when in hardcore mode', () => {
    // ARRANGE
    const achievementSet = createAchievementSet({
      medianTimeToCompleteHardcore: 3600,
      timesCompletedHardcore: 10,
      medianTimeToComplete: 7200,
      timesCompleted: 20,
    });

    const game = createGame({
      gameAchievementSets: [createGameAchievementSet({ achievementSet })],
      playersHardcore: 100,
      playersTotal: 200,
      medianTimeToBeatHardcore: 1800,
      timesBeatenHardcore: 15,
      medianTimeToBeat: 2700,
      timesBeaten: 25,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByText(/100 players/i)).toBeVisible();
    expect(screen.getByText(/50 players/i)).toBeVisible();
    expect(screen.getByText(/40 players/i)).toBeVisible();
  });

  it('shows softcore statistics when in softcore mode', async () => {
    // ARRANGE
    const achievementSet = createAchievementSet({
      medianTimeToCompleteHardcore: 3600,
      timesCompletedHardcore: 10,
      medianTimeToComplete: 7200,
      timesCompleted: 20,
    });

    const game = createGame({
      gameAchievementSets: [createGameAchievementSet({ achievementSet })],
      playersHardcore: 100,
      playersTotal: 200,
      medianTimeToBeatHardcore: 1800,
      timesBeatenHardcore: 15,
      medianTimeToBeat: 2700,
      timesBeaten: 25,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /toggle softcore/i }));

    // ASSERT
    expect(screen.getByText(/completed/i)).toBeVisible();
    expect(screen.queryByText(/mastered/i)).not.toBeInTheDocument();

    expect(screen.getByText(/100 players/i)).toBeVisible(); // !! totalPlayers in softcore = 200 - 100 = 100
    expect(screen.getByText(/75 players/i)).toBeVisible(); // !! numBeatenSoftcore
    expect(screen.getByText(/80 players/i)).toBeVisible(); // !! numCompletions
  });

  it('given there is an achievementsFirstPublishedAt date, displays it', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({
            achievementsFirstPublishedAt: new Date().toISOString(),
          }),
        }),
      ],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
      },
    });

    // ASSERT
    expect(screen.getByText(/achievements available since/i)).toBeVisible();
  });

  it('given targetAchievementSetPlayers props are provided, uses those instead of game player counts', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 20000,
      playersTotal: 40000,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
        targetAchievementSetPlayersTotal: 206, // !!
        targetAchievementSetPlayersHardcore: 167, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/167 players/i)).toBeVisible();
    expect(screen.getByText(/40 players/i)).toBeVisible();
  });

  it('given no targetAchievementSetPlayers props, falls back to game player counts', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [createGameAchievementSet()],
      playersHardcore: 100,
      playersTotal: 200,
    });

    render(<PlaytimeStatistics />, {
      pageProps: {
        backingGame: game,
        game,
        numBeaten: 50,
        numBeatenSoftcore: 75,
        numCompletions: 80,
        numMasters: 40,
        // !! no targetAchievementSetPlayers props provided
      },
    });

    // ASSERT
    expect(screen.getByText(/100 players/i)).toBeVisible();
    expect(screen.getByText(/40 players/i)).toBeVisible();
  });
});
