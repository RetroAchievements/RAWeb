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

  it('given the user has no progress on the game, does not set the progress bar to a hyperlink', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 33 });

    render(<PlayerGameProgressBar game={game} playerGame={null} />);

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
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

  it("given the user's highest award for the game is Mastered, renders those award details", () => {
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

  it("given the user's highest award for the game is Completed, renders those award details", () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 8,
      achievementsUnlockedSoftcore: 0,
      highestAward: createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 0 }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ASSERT
    expect(screen.getByText(/completed/i)).toBeVisible();
  });

  it("given the user's highest award for the game is Beaten (hardcore), renders those award details", () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 8,
      achievementsUnlockedSoftcore: 0,
      highestAward: createPlayerBadge({ awardType: AwardType.GameBeaten, awardDataExtra: 1 }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ASSERT
    expect(screen.getByText(/beaten/i)).toBeVisible();
    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it("given the user's highest award for the game is Beaten (softcore), renders those award details", () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 33 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 8,
      achievementsUnlockedHardcore: 0,
      achievementsUnlockedSoftcore: 8,
      highestAward: createPlayerBadge({ awardType: AwardType.GameBeaten, awardDataExtra: 0 }),
    });

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} />);

    // ASSERT
    expect(screen.getByText(/beaten \(softcore\)/i)).toBeVisible();
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

  it('given variant is "event" and pointsTotal equals achievementsPublished, does not show points metadata in the tooltip', async () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 400, pointsTotal: 400 });
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

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} variant="event" />);

    // ACT
    await userEvent.hover(screen.getByRole('progressbar'));

    // ASSERT
    const tooltipEl = await screen.findByRole('tooltip');

    expect(tooltipEl).not.toHaveTextContent(/points/i);
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

  it('passes along the given variant to the badge label', () => {
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

    render(<PlayerGameProgressBar game={game} playerGame={playerGame} variant="unmuted" />);

    // ASSERT
    const labelEl = screen.getByText(/mastered/i);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveClass('text-[gold]');
  });

  it("can be configured to show the player's progress percentage", () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 100, pointsTotal: 400 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 80,
      achievementsUnlockedHardcore: 80,
      achievementsUnlockedSoftcore: 0,
      pointsHardcore: game.pointsTotal,
      highestAward: createPlayerBadge({
        awardType: AwardType.GameBeaten,
        awardDataExtra: 1,
        awardDate: new Date('2023-05-06').toISOString(),
      }),
    });

    render(
      <PlayerGameProgressBar game={game} playerGame={playerGame} showProgressPercentage={true} />,
    );

    // ASSERT
    expect(screen.getByText(/80\%/i)).toBeVisible();
  });

  it('given it is configured to show progress percentage and the user has no progress, displays a "none" label', () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system, achievementsPublished: 100, pointsTotal: 400 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 0,
      achievementsUnlockedHardcore: 0,
      achievementsUnlockedSoftcore: 0,
      pointsHardcore: game.pointsTotal,
      highestAward: null,
    });

    render(
      <PlayerGameProgressBar game={game} playerGame={playerGame} showProgressPercentage={true} />,
    );

    // ASSERT
    expect(screen.getByText(/none/i)).toBeVisible();
  });
});
