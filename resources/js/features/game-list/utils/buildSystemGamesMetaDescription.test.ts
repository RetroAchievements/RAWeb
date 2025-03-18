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

  it('given the total games count is 10 or more, rounds down to nearest hundred and adds plus sign', () => {
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

  it('given the total games count is less than 10, shows exact count', () => {
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
