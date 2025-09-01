import { render, screen } from '@/test';
import { createAchievement, createPlayerGame } from '@/test/factories';

import { PlayerGameProgressLabel } from './PlayerGameProgressLabel';

describe('Component: PlayerGameProgressLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerGameProgressLabel achievements={[]} />, {
      pageProps: { playerGame: createPlayerGame() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the player has no unlocked achievements, renders nothing', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];
    const playerGame = createPlayerGame({ achievementsUnlocked: 0 });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('given there is no player game data, renders nothing', () => {
    // ARRANGE
    const achievements = [createAchievement()];

    render(<PlayerGameProgressLabel achievements={achievements} />, {
      pageProps: { playerGame: null },
    });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('given the player has unlocked all achievements in softcore mode, renders nothing', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01', points: 10 }),
      createAchievement({ unlockedAt: '2024-01-02', points: 20 }),
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 2,
      achievementsUnlockedSoftcore: 2,
      achievementsUnlockedHardcore: 0,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('given the player has unlocked all achievements in hardcore mode, renders nothing', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01', points: 10 }),
      createAchievement({ unlockedHardcoreAt: '2024-01-02', points: 20 }),
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 2,
      achievementsUnlockedHardcore: 2,
      achievementsUnlockedSoftcore: 0,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    expect(screen.queryByText(/unlocked/i)).not.toBeInTheDocument();
  });

  it('given the player has only hardcore achievements unlocked, displays hardcore progress', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01', points: 10, pointsWeighted: 15 }),
      createAchievement({ unlockedHardcoreAt: '2024-01-02', points: 20, pointsWeighted: 25 }),
      createAchievement({ points: 30 }), // not unlocked
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 2,
      achievementsUnlockedHardcore: 2,
      achievementsUnlockedSoftcore: 0,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    // "Unlocked 2 achievements worth 30 (40) points".
    expect(screen.getByText(/unlocked/i)).toBeVisible();
    expect(screen.getByText('2')).toBeVisible();
    expect(screen.getByText('30')).toBeVisible();
    expect(screen.getByText(/40/)).toBeVisible();

    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it('given the player has only softcore achievements unlocked, displays softcore progress', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01', points: 10 }),
      createAchievement({ unlockedAt: '2024-01-02', points: 20 }),
      createAchievement({ points: 30 }), // not unlocked
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 2,
      achievementsUnlockedSoftcore: 2,
      achievementsUnlockedHardcore: 0,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    // "Unlocked 2 softcore achievements worth 30 points".
    expect(screen.getByText(/unlocked/i)).toBeVisible();
    expect(screen.getByText('2')).toBeVisible();
    expect(screen.getByText('30')).toBeVisible();
    expect(screen.getByText(/softcore/i)).toBeVisible();

    expect(screen.queryByText(/\(/)).not.toBeInTheDocument(); // no weighted points for softcore mode
  });

  it('given the player has both hardcore and softcore achievements, displays both progress lines', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01', points: 10, pointsWeighted: 15 }),
      createAchievement({ unlockedAt: '2024-01-02', points: 20 }), // softcore only
      createAchievement({ points: 30 }), // not unlocked
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 2,
      achievementsUnlockedHardcore: 1,
      achievementsUnlockedSoftcore: 1,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    const unlockedTexts = screen.getAllByText(/unlocked/i);
    expect(unlockedTexts).toHaveLength(2);

    // "Unlocked 1 achievement worth 10 (15) points"
    expect(screen.getAllByText('1')[0]).toBeVisible();
    expect(screen.getByText('10')).toBeVisible();
    expect(screen.getByText(/15/)).toBeVisible();

    // "Unlocked 1 softcore achievement worth 20 points"
    expect(screen.getByText(/softcore/i)).toBeVisible();
    expect(screen.getByText('20')).toBeVisible();
  });

  it('given the player has zero points but some achievements unlocked, still displays the label', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01', points: 0, pointsWeighted: 0 }),
      createAchievement({ points: 10 }), // not unlocked
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 1,
      achievementsUnlockedHardcore: 1,
      achievementsUnlockedSoftcore: 0,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    expect(screen.getByText(/unlocked/i)).toBeVisible();
    expect(screen.getByText('1')).toBeVisible();
    expect(screen.getByText('0')).toBeVisible();
  });

  it('given the achievements have mixed unlock states, correctly calculates stats', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01', points: 5, pointsWeighted: 8 }),
      createAchievement({ unlockedHardcoreAt: '2024-01-02', points: 10, pointsWeighted: 12 }),
      createAchievement({ unlockedAt: '2024-01-03', points: 15 }), // softcore only
      createAchievement({ unlockedAt: '2024-01-04', points: 20 }), // softcore only
      createAchievement({ points: 25 }), // not unlocked
    ];
    const playerGame = createPlayerGame({
      achievementsUnlocked: 4,
      achievementsUnlockedHardcore: 2,
      achievementsUnlockedSoftcore: 2,
    });

    render(<PlayerGameProgressLabel achievements={achievements} />, { pageProps: { playerGame } });

    // ASSERT
    // Hardcore: 2 achievements, 15 points (5+10), 20 weighted (8+12).
    expect(screen.getAllByText('2')[0]).toBeVisible();
    expect(screen.getByText('15')).toBeVisible();
    expect(screen.getByText(/20/)).toBeVisible();

    // Softcore: 2 achievements, 35 points (15+20).
    expect(screen.getByText(/softcore/i)).toBeVisible();
    expect(screen.getByText('35')).toBeVisible();
  });
});
