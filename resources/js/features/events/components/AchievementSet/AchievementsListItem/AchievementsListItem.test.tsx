import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementsListItem } from './AchievementsListItem';

/**
 * JSDOM can't handle the CSS and layout calculations this component is doing.
 * Therefore, instead of .toBeVisible(), we're forced to use .toBeInTheDocument().
 */

describe('Component: AchievementsListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockHardcorePercentage: '0.1',
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the achievement has a game title, displays the game information', () => {
    // ARRANGE
    render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockHardcorePercentage: '0.1',
          game: createGame({
            title: 'Test Game',
          }),
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    expect(screen.getByText(/from/i)).toBeInTheDocument();

    // ... these are separated due to word wrap logic ...
    expect(screen.getByText('Test')).toBeInTheDocument();
    expect(screen.getByText('Game')).toBeInTheDocument();
  });

  it('given the achievement has no game title, does not crash', () => {
    // ARRANGE
    const { container } = render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockHardcorePercentage: '0.1',
          game: undefined,
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given hardcore unlocks equal total unlocks, displays only one number in bold', () => {
    // ARRANGE
    render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: 100,
          unlocksHardcoreTotal: 100,
          unlockHardcorePercentage: '0.1',
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    const unlockCount = screen.getByTitle(/total unlocks/i);
    expect(unlockCount).toHaveClass('font-bold');
  });

  it('given hardcore unlocks are different from total unlocks, displays both numbers', () => {
    // ARRANGE
    render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockHardcorePercentage: '0.1',
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    const totalUnlocks = screen.getByTitle(/total unlocks/i);
    const hardcoreUnlocks = screen.getByTitle(/hardcore unlocks/i);

    expect(totalUnlocks).toBeInTheDocument();
    expect(hardcoreUnlocks).toBeInTheDocument();
    expect(totalUnlocks).not.toHaveClass('font-bold');
    expect(hardcoreUnlocks).toHaveClass('font-bold');
  });

  it('given an achievement with null unlock stats, uses default values of 0', () => {
    // ARRANGE
    render(
      <AchievementsListItem
        achievement={createAchievement({
          title: 'Test Achievement',
          description: 'Test Description',
          unlocksTotal: undefined,
          unlocksHardcoreTotal: undefined,
          unlockHardcorePercentage: undefined,
        })}
        index={0}
        isLargeList={false}
        playersTotal={1000}
      />,
    );

    // ASSERT
    expect(screen.getByText('0')).toBeInTheDocument();
  });
});
