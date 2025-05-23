import { __UNSAFE_VERY_DANGEROUS_SLEEP, render, screen, waitFor } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementsListItem } from './AchievementsListItem';

describe('Component: AchievementsListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Test Achievement',
      description: 'A test achievement description',
      game: createGame({ title: 'Test Game' }),
    });

    const { container } = render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={100}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement, displays its title and description', async () => {
    // ARRANGE
    const title = 'Master Collector';
    const description = 'Find all hidden gems in the game';
    const achievement = createAchievement({
      title,
      description,
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={100}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(title)).toBeVisible();
    });
    expect(screen.getByText(description)).toBeVisible();
  });

  it('given an achievement with a game (probably an event achievement), displays the game title', async () => {
    // ARRANGE
    const gameTitle = 'Super Adventure Game';
    const achievement = createAchievement({
      game: createGame({ title: gameTitle }),
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={100}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/super/i)).toBeVisible();
    });
  });

  it('given playersTotal is null, does not render the progress bar section', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockPercentage: '45.5',
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={null}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/unlock rate/i)).not.toBeInTheDocument();
  });

  it('given an achievement has no points or weighted points, does not display points', () => {
    // ARRANGE
    const achievement = createAchievement({
      points: undefined,
      pointsWeighted: undefined,
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={null}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/undefined/i)).not.toBeInTheDocument();
  });

  it('given an achievement has points and weighted points, displays points', async () => {
    // ARRANGE
    const achievement = createAchievement({
      points: 50,
      pointsWeighted: 100,
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={null}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/50/i)).toBeVisible();
    });
    expect(screen.getByText(/100/i)).toBeVisible();
  });

  it('given an achievement is worth 0 points, does not display points', async () => {
    // ARRANGE
    const achievement = createAchievement({
      points: 0,
      pointsWeighted: 100,
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={null}
      />,
    );

    // ASSERT
    await __UNSAFE_VERY_DANGEROUS_SLEEP(100); // let any animations finish up
    expect(screen.queryByText(/100/i)).not.toBeInTheDocument();
  });

  it('given an achievement has a type, displays a type indicator', async () => {
    // ARRANGE
    const achievement = createAchievement({
      type: 'progression',
    });

    render(
      <AchievementsListItem
        achievement={achievement}
        index={0}
        isLargeList={false}
        playersTotal={100}
      />,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByLabelText(/progression/i)[0]).toBeVisible();
    });
  });
});
