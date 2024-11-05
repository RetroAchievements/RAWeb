import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createGame, createPlayerBadge, createPlayerGame, createSystem } from '@/test/factories';

import { ChipOfInterest } from './ChipOfInterest';

describe('Component: ChipOfInterest', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChipOfInterest game={createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no current sort field, renders nothing', () => {
    // ARRANGE
    const { container } = render(<ChipOfInterest game={createGame()} />);

    // ASSERT
    // eslint-disable-next-line testing-library/no-container -- we have no other way of testing this
    const chipEl = container.querySelector('span');

    expect(chipEl).toBeNull();
  });

  it('given the field is achievementsPublished, shows the number of achievements for the game', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 123 });

    render(<ChipOfInterest game={game} fieldId="achievementsPublished" />);

    // ASSERT
    expect(screen.getByText(/123/i)).toBeVisible();
  });

  it('given the field is achievementsPublished and the game has no achievements, renders zero', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: undefined });

    render(<ChipOfInterest game={game} fieldId="achievementsPublished" />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
  });

  it('given the field is pointsTotal, shows the number of points for the game', () => {
    // ARRANGE
    const game = createGame({ pointsTotal: 1555 });

    render(<ChipOfInterest game={game} fieldId="pointsTotal" />);

    // ASSERT
    expect(screen.getByText(/1,555/i)).toBeVisible();
  });

  it('given the field is pointsTotal and the game has no points, renders zero', () => {
    // ARRANGE
    const game = createGame({ pointsTotal: undefined });

    render(<ChipOfInterest game={game} fieldId="pointsTotal" />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
  });

  it('given the field is retroRatio, shows the rarity of the game', () => {
    // ARRANGE
    const game = createGame({ pointsTotal: 100, pointsWeighted: 400 });

    render(<ChipOfInterest game={game} fieldId="retroRatio" />);

    // ASSERT
    expect(screen.getByText('×4.00')).toBeVisible();
  });

  it('given the field is retroRatio and the game has no points, shows a fallback label', () => {
    // ARRANGE
    const game = createGame({ pointsTotal: 0, pointsWeighted: 400 });

    render(<ChipOfInterest game={game} fieldId="retroRatio" />);

    // ASSERT
    expect(screen.getByText('none')).toBeVisible();
  });

  it('given the field is lastUpdated, shows the last updated date of the game', () => {
    // ARRANGE
    const game = createGame({ lastUpdated: new Date('2023-05-05').toISOString() });

    render(<ChipOfInterest game={game} fieldId="lastUpdated" />);

    // ASSERT
    expect(screen.getByText('May 5, 2023')).toBeVisible();
  });

  it('given the field is lastUpdated and there is no last updated date, shows a fallback label', () => {
    // ARRANGE
    const game = createGame({ lastUpdated: undefined });

    render(<ChipOfInterest game={game} fieldId="lastUpdated" />);

    // ASSERT
    expect(screen.getByText(/unknown/i)).toBeVisible();
  });

  it('given the field is releasedAt, shows the release date of the game', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: new Date('1987-05-05').toISOString(),
      releasedAtGranularity: 'day',
    });

    render(<ChipOfInterest game={game} fieldId="releasedAt" />);

    // ASSERT
    expect(screen.getByText('May 5, 1987')).toBeVisible();
  });

  it('given the field is releasedAt, respects the granularity of the game release date', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: new Date('1987-05-05').toISOString(),
      releasedAtGranularity: 'month',
    });

    render(<ChipOfInterest game={game} fieldId="releasedAt" />);

    // ASSERT
    expect(screen.getByText('May 1987')).toBeVisible();
  });

  it('given the field is releasedAt and there is no release date, shows a fallback label', () => {
    // ARRANGE
    const game = createGame({ releasedAt: undefined });

    render(<ChipOfInterest game={game} fieldId="releasedAt" />);

    // ASSERT
    expect(screen.getByText(/unknown/i)).toBeVisible();
  });

  it('given the field is playersTotal, shows the player count of the game', () => {
    // ARRANGE
    const game = createGame({ playersTotal: 32651 });

    render(<ChipOfInterest game={game} fieldId="playersTotal" />);

    // ASSERT
    expect(screen.getByText(/32,651/i)).toBeVisible();
  });

  it('given the field is playersTotal and the player count is unknown, falls back to zero', () => {
    // ARRANGE
    const game = createGame({ playersTotal: undefined });

    render(<ChipOfInterest game={game} fieldId="playersTotal" />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
  });

  it('given the field is numVisibleLeaderboards, shows the number of visible leaderboards of the game', () => {
    // ARRANGE
    const game = createGame({ numVisibleLeaderboards: 123 });

    render(<ChipOfInterest game={game} fieldId="numVisibleLeaderboards" />);

    // ASSERT
    expect(screen.getByText(/123/i)).toBeVisible();
  });

  it('given the field is numVisibleLeaderboards and the number of visible leaderboards is unknown, falls back to zero', () => {
    // ARRANGE
    const game = createGame({ numVisibleLeaderboards: undefined });

    render(<ChipOfInterest game={game} fieldId="numVisibleLeaderboards" />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
  });

  it('given the field is numUnresolvedTickets, shows the number of unresolved tickets associated with the game', () => {
    // ARRANGE
    const game = createGame({ numUnresolvedTickets: 123 });

    render(<ChipOfInterest game={game} fieldId="numUnresolvedTickets" />);

    // ASSERT
    expect(screen.getByText(/123/i)).toBeVisible();
  });

  it('given the field is numUnresolvedTickets and the number of unresolved tickets is unknown, falls back to zero', () => {
    // ARRANGE
    const game = createGame({ numUnresolvedTickets: undefined });

    render(<ChipOfInterest game={game} fieldId="numUnresolvedTickets" />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
  });

  it('given the field is hasActiveOrInReviewClaims and the game is claimed, shows a chip', () => {
    // ARRANGE
    const game = createGame({ hasActiveOrInReviewClaims: true });

    render(<ChipOfInterest game={game} fieldId="hasActiveOrInReviewClaims" />);

    // ASSERT
    expect(screen.getByText(/claimed/i)).toBeVisible();
  });

  it('given the field is hasActiveOrInReviewClaims and the game is not claimed, does not show a chip', () => {
    // ARRANGE
    const game = createGame({ hasActiveOrInReviewClaims: false });

    render(<ChipOfInterest game={game} fieldId="hasActiveOrInReviewClaims" />);

    // ASSERT
    expect(screen.queryByText(/claimed/i)).not.toBeInTheDocument();
  });

  it('given the field is progress and the player has mastered the game, renders a mastery symbol with no 100% label', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 1 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 100,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
      }),
    });

    render(<ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />);

    // ASSERT
    expect(screen.queryByText(/100/i)).not.toBeInTheDocument();
    expect(screen.getByRole('img', { name: /mastered indicator/i })).toBeVisible();
  });

  it('given the field is progress and the player has not started the game, renders nothing', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 1 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 0,
      highestAward: undefined,
    });

    const { container } = render(
      <ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />,
    );

    // ASSERT
    // eslint-disable-next-line testing-library/no-container -- we have no other way of testing this
    const chipEl = container.querySelector('span');

    expect(chipEl).toBeNull();
    expect(screen.queryByText(/0/i)).not.toBeInTheDocument();
  });

  it('given the field is progress and the player has no awards, renders the percentage', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 1 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 11,
      highestAward: undefined,
    });

    render(<ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />);

    // ASSERT
    expect(screen.getByText(/11%/i)).toBeVisible();
  });

  it('given the user has beaten the game, colorizes the percentage text', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 1 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 11,
      highestAward: createPlayerBadge({
        awardType: AwardType.GameBeaten,
        awardDataExtra: 1,
      }),
    });

    render(<ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />);

    // ASSERT
    const percentageLabel = screen.getByText(/11%/i);

    expect(percentageLabel).toBeVisible();
    expect(percentageLabel).toHaveClass('text-zinc-300');
  });

  it('given the user has mastered the game but not unlocked all the published achievements, colorizes the text', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 1 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 99,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
      }),
    });

    render(<ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />);

    // ASSERT
    const percentageLabel = screen.getByText(/99%/i);

    expect(percentageLabel).toBeVisible();
    expect(percentageLabel).toHaveClass('text-[gold]');
  });

  it('given the game is an "Events" system game, never renders an indicator', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100, system: createSystem({ id: 101 }) });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 100,
      highestAward: createPlayerBadge({
        awardType: AwardType.Mastery,
        awardDataExtra: 1,
      }),
    });

    render(<ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />);

    // ASSERT
    expect(screen.queryByText(/mastered indicator/i)).not.toBeInTheDocument();
  });
});
