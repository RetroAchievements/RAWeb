import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGameAchievementSet,
} from '@/test/factories';

import { GameAchievementSetHeader } from './GameAchievementSetHeader';

describe('Component: GameAchievementSetHeader', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameAchievementSetHeader
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no title is provided, shows "Base Set" as the title', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: null,
    });

    render(
      <GameAchievementSetHeader
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    expect(screen.getByText(/base set/i)).toBeVisible();
  });

  it('given a title is provided, shows that title', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: 'Professor Oak Challenge',
    });

    render(
      <GameAchievementSetHeader
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    expect(screen.getByText('Professor Oak Challenge')).toBeVisible();
  });

  it('given it is not the only set for the game and is closed, shows a downward-facing chevron', () => {
    // ARRANGE
    render(
      <GameAchievementSetHeader
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    const icon = screen.getByTestId('chevron');
    expect(icon).toHaveClass('rotate-0');
  });

  it('given it is not the only set for the game and is open, shows an upward-facing chevron', () => {
    // ARRANGE
    render(
      <GameAchievementSetHeader
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={false}
        isOpen={true}
      />,
    );

    // ASSERT
    const icon = screen.getByTestId('chevron');
    expect(icon).toHaveClass('rotate-180');
  });

  it('given it is the only set for the game, does not show a chevron', () => {
    // ARRANGE
    render(
      <GameAchievementSetHeader
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={true}
        isOpen={false}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('chevron')).not.toBeInTheDocument();
  });

  it('shows the achievement set image', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet();
    const { imageAssetPathUrl } = gameAchievementSet.achievementSet;

    render(
      <GameAchievementSetHeader
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    const imgElement = screen.getByRole('img');
    expect(imgElement).toBeVisible();
    expect(imgElement).toHaveAttribute('src', imageAssetPathUrl);
  });

  it('shows the achievement count and points information', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [
          createAchievement({ points: 10, pointsWeighted: 5 }),
          createAchievement({ points: 20, pointsWeighted: 10 }),
        ],
      }),
    });

    render(
      <GameAchievementSetHeader
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
        isOpen={false}
      />,
    );

    // ASSERT
    expect(screen.getByText(/2 achievements worth 30/i)).toBeVisible();
    expect(screen.getByText(/15/i)).toBeVisible();
  });
});
