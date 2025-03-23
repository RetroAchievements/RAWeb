import { buildSystemGamesMetaDescription } from './buildSystemGamesMetaDescription';

describe('Util: buildSystemGamesMetaDescription', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const systemName = 'SNES';
    const totalGames = 100;

    // ACT
    const result = buildSystemGamesMetaDescription(totalGames, systemName);

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('given exactly 100 games, rounds to 100+ games', () => {
    // ARRANGE
    const systemName = 'NES';
    const totalGames = 100;

    // ACT
    const result = buildSystemGamesMetaDescription(totalGames, systemName);

    // ASSERT
    expect(result).toEqual(
      'Explore 100+ NES games on RetroAchievements. Track your progress as you beat and master each title.',
    );
  });

  it('given a count between 10-99, rounds down to the nearest 10 and adds a plus sign', () => {
    // ARRANGE
    const systemName = 'Vectrex';
    const totalGames = 42;

    // ACT
    const result = buildSystemGamesMetaDescription(totalGames, systemName);

    // ASSERT
    expect(result).toEqual(
      'Explore 40+ Vectrex games on RetroAchievements. Track your progress as you beat and master each title.',
    );
  });

  it('given a very large count, rounds down to the nearest hundred and adds a plus sign', () => {
    // ARRANGE
    const systemName = 'SNES';
    const totalGames = 2345;

    // ACT
    const result = buildSystemGamesMetaDescription(totalGames, systemName);

    // ASSERT
    expect(result).toEqual(
      'Explore 2,300+ SNES games on RetroAchievements. Track your progress as you beat and master each title.',
    );
  });

  it('given the total games count is less than 10, shows the exact count', () => {
    // ARRANGE
    const systemName = 'Virtual Boy';
    const totalGames = 7;

    // ACT
    const result = buildSystemGamesMetaDescription(totalGames, systemName);

    // ASSERT
    expect(result).toEqual(
      'Explore 7 Virtual Boy games on RetroAchievements. Track your progress as you beat and master each title.',
    );
  });
});
