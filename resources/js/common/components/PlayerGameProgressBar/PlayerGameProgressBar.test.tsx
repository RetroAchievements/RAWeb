import userEvent from '@testing-library/user-event';

import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createGame, createPlayerBadge, createPlayerGame, createSystem } from '@/test/factories';

import { PlayerGameProgressBar } from './PlayerGameProgressBar';

describe('Component: PlayerGameProgressBar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerGameProgressBar game={createGame()} playerGame={null} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has no published achievements, renders nothing', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 0 });

    render(<PlayerGameProgressBar game={game} playerGame={null} />);

    // ASSERT
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it('given the user has no progress on the game, renders a progress bar containing no progress', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 33 });

    render(<PlayerGameProgressBar game={game} playerGame={null} />);

    // ASSERT
    const progressBarEl = screen.getByRole('progressbar');

    expect(progressBarEl).toBeVisible();
    expect(progressBarEl).toHaveAttribute('aria-valuemax', String(game.achievementsPublished));
    expect(progressBarEl).toHaveAttribute('aria-valuenow', '0');
  });

  it('given the user has progress on the game, renders a progress bar containing progress', () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 8,
      achievementsUnlockedSoftcore: 0,
      highestAward: null,
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ASSERT
    const progressBarEl = screen.getByRole('progressbar');

    expect(progressBarEl).toBeVisible();
    expect(progressBarEl).toHaveAttribute('aria-valuemax', String(game.achievementsPublished));
    expect(progressBarEl).toHaveAttribute('aria-valuenow', '8');
  });

  it('given the user has a badge on the game, renders those badge details', () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 8,
      achievementsUnlockedSoftcore: 0,
      highestAward: createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1 }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ASSERT
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('given the user has progress on the game and hovers over the progress bar, shows a tooltip with more details', async () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33, pointsTotal: 400 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 8,
      achievementsUnlockedSoftcore: 0,
      pointsHardcore: 285,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
        awardDate: new Date('2023-05-06').toISOString(),
      }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ACT
    await userEvent.hover(screen.getByRole('progressbar'));

    // ASSERT
    const tooltipEl = await screen.findByRole('tooltip');

    expect(tooltipEl).toHaveTextContent(/8 of 33 achievements/i);
    expect(tooltipEl).toHaveTextContent(/285 of 400 points/i);
    expect(tooltipEl).toHaveTextContent(/mastered/i);
  });

  it('given the user has unlocked all the achievements for the game, shows no achievements or points metadata in the tooltip', async () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33, pointsTotal: 400 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: game.achievementsPublished,
      achievementsUnlockedHardcore: game.achievementsPublished,
      achievementsUnlockedSoftcore: 0,
      pointsHardcore: game.pointsTotal,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
        awardDate: new Date('2023-05-06').toISOString(),
      }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ACT
    await userEvent.hover(screen.getByRole('progressbar'));

    // ASSERT
    const tooltipEl = await screen.findByRole('tooltip');

    expect(tooltipEl).not.toHaveTextContent(/achievements/i);
    expect(tooltipEl).not.toHaveTextContent(/points/i);
    expect(tooltipEl).toHaveTextContent(/mastered/i);
  });
});
