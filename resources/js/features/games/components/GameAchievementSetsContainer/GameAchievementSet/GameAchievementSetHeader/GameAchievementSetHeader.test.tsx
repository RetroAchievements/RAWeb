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
      <GameAchievementSetHeader gameAchievementSet={createGameAchievementSet()} />,
      {
        pageProps: { isViewingPublishedAchievements: true },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no title is provided, shows "Base Set" as the title', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: null,
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByText(/base set/i)).toBeVisible();
  });

  it('given a title is provided, shows that title', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: 'Professor Oak Challenge',
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByText('Professor Oak Challenge')).toBeVisible();
  });

  it('given a title is provided and type is not core, shows the Subset tag', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: 'Professor Oak Challenge',
      type: 'bonus',
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByText(/subset/i)).toBeVisible();
  });

  it('given no title is provided, does not show the Subset tag', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: null,
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.queryByText(/subset/i)).not.toBeInTheDocument();
  });

  it('given type is core with a title, does not show the Subset tag', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      title: 'Some Title',
      type: 'core',
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.queryByText(/subset/i)).not.toBeInTheDocument();
  });

  it('given it is the only set for the game, does not show a chevron', () => {
    // ARRANGE
    render(<GameAchievementSetHeader gameAchievementSet={createGameAchievementSet()} />);

    // ASSERT
    expect(screen.queryByTestId('chevron')).not.toBeInTheDocument();
  });

  it('shows the achievement set image', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet();
    const { imageAssetPathUrl } = gameAchievementSet.achievementSet;

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

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

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByText(/2/i)).toBeVisible();
    expect(screen.getByText(/achievements worth/i)).toBeVisible();
    expect(screen.getByText(/30/i)).toBeVisible();
    expect(screen.getByText(/15/i)).toBeVisible();
  });

  it('given the set has no achievements, shows the correct label', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [], // !!
      }),
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByText(/there are no achievements for this set yet/i)).toBeVisible();
  });

  it('given the user is viewing unpublished achievements and there are achievements, shows the correct label', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [createAchievement(), createAchievement()], // !!
      }),
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: false }, // !!
    });

    // ASSERT
    expect(screen.getByText(/unpublished achievements/i)).toBeVisible();
  });

  it('given the user is viewing unpublished achievements and there are not any achievements, shows the correct label', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [], // !!
      }),
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: false }, // !!
    });

    // ASSERT
    expect(screen.getByText(/there are currently no unpublished achievements/i)).toBeVisible();
  });

  it('given weighted points is 0, does not show weighted points or rarity', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [
          createAchievement({ points: 10, pointsWeighted: 0 }), // !!
          createAchievement({ points: 20, pointsWeighted: 0 }), // !!
        ],
      }),
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.queryByTestId('ratio-container')).not.toBeInTheDocument();
  });

  it('given weighted points is not 0, shows weighted points and rarity', () => {
    // ARRANGE
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [
          createAchievement({ points: 10, pointsWeighted: 15 }),
          createAchievement({ points: 20, pointsWeighted: 25 }),
        ],
      }),
    });

    render(<GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />, {
      pageProps: { isViewingPublishedAchievements: true },
    });

    // ASSERT
    expect(screen.getByTestId('ratio-container')).toBeVisible();
    expect(screen.getByText(/40/i)).toBeVisible();
  });
});
