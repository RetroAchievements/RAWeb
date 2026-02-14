import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementHero } from './AchievementHero';

describe('Component: AchievementHero', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
      unlockPercentage: '25',
    });

    const { container } = render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the achievement title, description, points, and RetroPoints', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Beat the Final Boss',
      description: 'Defeat the last enemy',
      points: 25,
      pointsWeighted: 200,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /beat the final boss/i })).toBeVisible();
    expect(screen.getByText(/defeat the last enemy/i)).toBeVisible();
    expect(screen.getAllByText(/points/i).length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText(/retropoints/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given the user has unlocked the achievement, shows the unlocked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-03-10T12:00:00Z',
      badgeUnlockedUrl: '/badge/unlocked.png',
      badgeLockedUrl: '/badge/locked.png',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('src', '/badge/unlocked.png');
  });

  it('given the user has not unlocked the achievement, shows the locked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      badgeUnlockedUrl: '/badge/unlocked.png',
      badgeLockedUrl: '/badge/locked.png',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('src', '/badge/locked.png');
  });

  it('given the achievement has a type, displays the type indicator', () => {
    // ARRANGE
    const achievement = createAchievement({
      type: 'missable',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/missable/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given the achievement has no type, does not display a type indicator label', () => {
    // ARRANGE
    const achievement = createAchievement({
      type: null,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/missable/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/progression/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/win condition/i)).not.toBeInTheDocument();
  });

  it('given the user unlocked hardcore, shows the hardcore unlock label', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedHardcoreAt: '2024-06-15T08:30:00Z',
      unlockedAt: '2024-06-15T08:30:00Z',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/unlocked hardcore/i)).toBeVisible();
  });

  it('given the user unlocked softcore only, shows the softcore unlock label', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-06-15T08:30:00Z',
      unlockedHardcoreAt: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText('Unlocked')).toBeVisible();
    expect(screen.queryByText(/unlocked hardcore/i)).not.toBeInTheDocument();
  });

  it('given the user has not unlocked the achievement, does not show an unlock status', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('displays the unlock rate percentage', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockPercentage: '0.4567',
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/45\.67/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given no unlock percentage, falls back to zero', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockPercentage: undefined,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getAllByText(/0\.00/i).length).toBeGreaterThanOrEqual(1);
  });

  it('displays softcore and hardcore unlock counts', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 500,
      unlocksHardcore: 300,
    });

    render(<AchievementHero />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/200 softcore/i)).toBeVisible();
    expect(screen.getByText(/300 hardcore/i)).toBeVisible();
  });
});
