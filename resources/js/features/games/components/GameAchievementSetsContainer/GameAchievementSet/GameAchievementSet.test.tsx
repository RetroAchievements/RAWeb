import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGameAchievementSet,
} from '@/test/factories';

import { GameAchievementSet } from './GameAchievementSet';

describe('Component: GameAchievementSet', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameAchievementSet
        achievements={[]}
        currentSort="normal"
        gameAchievementSet={createGameAchievementSet()}
        isInitiallyOpened={false}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an empty achievements array, renders an empty achievement list', () => {
    // ARRANGE
    render(
      <GameAchievementSet
        achievements={[]}
        currentSort="normal"
        gameAchievementSet={createGameAchievementSet()}
        isInitiallyOpened={true}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /achievement/i })).not.toBeInTheDocument();
  });

  it('given an achievement list longer than 50 items, renders all items', () => {
    // ARRANGE
    const achievements = Array.from({ length: 51 }, (_, i) =>
      createAchievement({
        id: i + 1,
        title: `Achievement ${i + 1}`,
      }),
    );

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        currentSort="normal"
        gameAchievementSet={gameAchievementSet}
        isInitiallyOpened={true}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('listitem').length).toBeGreaterThanOrEqual(50);
  });

  it('given the collapsible is not initially opened, does not show achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ title: 'Hidden Achievement' });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [achievement],
      }),
    });

    render(
      <GameAchievementSet
        achievements={[achievement]}
        currentSort="normal"
        gameAchievementSet={gameAchievementSet}
        isInitiallyOpened={false}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    expect(screen.queryByText('Hidden Achievement')).not.toBeVisible();
  });

  it('given the collapsible is initially opened, shows achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ title: 'Visible Achievement' });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [achievement],
      }),
    });

    render(
      <GameAchievementSet
        achievements={[achievement]}
        currentSort="normal"
        gameAchievementSet={gameAchievementSet}
        isInitiallyOpened={true}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    expect(screen.getByText('Visible Achievement')).toBeVisible();
  });

  it('given the current sort changes, re-renders the achievement list', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ title: 'B Achievement' }),
      createAchievement({ title: 'A Achievement' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    const { rerender } = render(
      <GameAchievementSet
        achievements={achievements}
        currentSort="normal"
        gameAchievementSet={gameAchievementSet}
        isInitiallyOpened={true}
        isOnlySetForGame={false}
      />,
    );

    // ACT
    rerender(
      <GameAchievementSet
        achievements={achievements}
        currentSort="-normal"
        gameAchievementSet={gameAchievementSet}
        isInitiallyOpened={true}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    const items = screen.getAllByRole('listitem');
    expect(items.length).toBeGreaterThanOrEqual(2);
  });

  it('given it is the only set for the game, disables the collapsible', () => {
    // ARRANGE
    render(
      <GameAchievementSet
        achievements={[createAchievement()]}
        currentSort="normal"
        gameAchievementSet={createGameAchievementSet()}
        isInitiallyOpened={false}
        isOnlySetForGame={true}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button')).toBeDisabled();
  });
});
