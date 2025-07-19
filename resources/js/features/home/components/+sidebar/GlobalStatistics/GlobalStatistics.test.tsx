import { render, screen } from '@/test';
import { createHomePageProps, createStaticData } from '@/test/factories';

import { GlobalStatistics } from './GlobalStatistics';

describe('Component: GlobalStatistics', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GlobalStatistics />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /statistics/i })).toBeVisible();
  });

  it('displays the correct count of games and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({ staticData: createStaticData({ numGames: 8700 }) }),
    });

    // ASSERT
    const gamesEl = screen.getByLabelText('Games');

    expect(gamesEl).toBeVisible();
    expect(gamesEl).toHaveTextContent('8,700');

    expect(screen.getByRole('link', { name: 'Games' })).toHaveAttribute('href', 'game.index');
  });

  it('displays the correct count of achievements and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ numAchievements: 420_000 }),
      }),
    });

    // ASSERT
    const achievementsEl = screen.getByLabelText('Achievements');

    expect(achievementsEl).toBeVisible();
    expect(achievementsEl).toHaveTextContent('420,000');

    expect(screen.getByRole('link', { name: 'Achievements' })).toHaveAttribute(
      'href',
      '/achievementList.php',
    );
  });

  it('displays the correct count of sets mastered and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ numHardcoreMasteryAwards: 318_450 }),
      }),
    });

    // ASSERT
    const gamesMasteredEl = screen.getByLabelText('Sets Mastered');

    expect(gamesMasteredEl).toBeVisible();
    expect(gamesMasteredEl).toHaveTextContent('318,450');

    expect(screen.getByRole('link', { name: 'Sets Mastered' })).toHaveAttribute(
      'href',
      '/recentMastery.php?t=1&m=1',
    );
  });

  it('displays the correct count of games beaten and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ numHardcoreGameBeatenAwards: 623_999 }),
      }),
    });

    // ASSERT
    const gamesBeatenEl = screen.getByLabelText('Games Beaten');

    expect(gamesBeatenEl).toBeVisible();
    expect(gamesBeatenEl).toHaveTextContent('623,999');

    expect(screen.getByRole('link', { name: 'Games Beaten' })).toHaveAttribute(
      'href',
      '/recentMastery.php?t=8&m=1',
    );
  });

  it('displays the correct count of registered players and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ numRegisteredUsers: 914_370 }),
      }),
    });

    // ASSERT
    const registeredPlayersEl = screen.getByLabelText('Registered Players');

    expect(registeredPlayersEl).toBeVisible();
    expect(registeredPlayersEl).toHaveTextContent('914,370');

    expect(screen.getByRole('link', { name: 'Registered Players' })).toHaveAttribute(
      'href',
      '/userList.php',
    );
  });

  it('displays the correct count of achievement unlocks and has the right href on that element', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ numAwarded: 74_350_932 }),
      }),
    });

    // ASSERT
    const achievementUnlocksEl = screen.getByLabelText('Achievement Unlocks');

    expect(achievementUnlocksEl).toBeVisible();
    expect(achievementUnlocksEl).toHaveTextContent('74,350,932');

    expect(screen.getByRole('link', { name: 'Achievement Unlocks' })).toHaveAttribute(
      'href',
      '/recentMastery.php',
    );
  });

  it('displays the correct count of points earned since site launch', () => {
    // ARRANGE
    render(<GlobalStatistics />, {
      pageProps: createHomePageProps({
        staticData: createStaticData({ totalPointsEarned: 1_234_567_890 }),
      }),
    });

    // ASSERT
    const pointsEarnedEl = screen.getByText('1,234,567,890');

    expect(pointsEarnedEl).toBeVisible();
    expect(pointsEarnedEl).toHaveTextContent('1,234,567,890');
  });
});
