import { createAchievement, createGame, createSystem } from '@/test/factories';
import { renderHook } from '@/test/setup';

import { useAchievementMetaDescription } from './useAchievementMetaDescription';

describe('Hook: useAchievementMetaDescription', () => {
  it('is defined', () => {
    // ASSERT
    expect(useAchievementMetaDescription).toBeDefined();
  });

  it('given an achievement with no type, builds the description without a type label', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ title: 'Super Mario Bros.', system });

    const achievement = createAchievement({
      description: 'Beat the game',
      points: 10,
      unlocksTotal: 500,
      type: null,
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual(
      'Beat the game [10 points], won by 500 players - Super Mario Bros. for NES',
    );
  });

  it('given an achievement with a progression type, includes the type label', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: 'Super Mario World', system });

    const achievement = createAchievement({
      description: 'Complete World 1',
      points: 5,
      unlocksTotal: 1200,
      type: 'progression',
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual(
      'Complete World 1 [5 points, Progression], won by 1,200 players - Super Mario World for SNES',
    );
  });

  it('given an achievement with a win_condition type, includes the type label', () => {
    // ARRANGE
    const system = createSystem({ name: 'Genesis/Mega Drive' });
    const game = createGame({ title: 'Sonic the Hedgehog', system });

    const achievement = createAchievement({
      description: 'Defeat Dr. Robotnik',
      points: 25,
      unlocksTotal: 300,
      type: 'win_condition',
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual(
      'Defeat Dr. Robotnik [25 points, Win Condition], won by 300 players - Sonic the Hedgehog for Genesis/Mega Drive',
    );
  });

  it('given an achievement with a missable type, includes the type label', () => {
    // ARRANGE
    const system = createSystem({ name: 'PlayStation' });
    const game = createGame({ title: 'Final Fantasy VII', system });

    const achievement = createAchievement({
      description: 'Get the secret item',
      points: 50,
      unlocksTotal: 87,
      type: 'missable',
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual(
      'Get the secret item [50 points, Missable], won by 87 players - Final Fantasy VII for PlayStation',
    );
  });

  it('given exactly 1 point, uses the singular "point"', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ title: 'Some Game', system });

    const achievement = createAchievement({
      description: 'Do a thing',
      points: 1,
      unlocksTotal: 10,
      type: null,
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual('Do a thing [1 point], won by 10 players - Some Game for NES');
  });

  it('given exactly 1 winner, uses the singular "player"', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ title: 'Some Game', system });

    const achievement = createAchievement({
      description: 'Do a thing',
      points: 10,
      unlocksTotal: 1,
      type: null,
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual('Do a thing [10 points], won by 1 player - Some Game for NES');
  });

  it('given a large winner count, formats the number with locale separators', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ title: 'Popular Game', system });

    const achievement = createAchievement({
      description: 'Easy achievement',
      points: 5,
      unlocksTotal: 12345,
      type: null,
    });

    // ACT
    const { result } = renderHook(() => useAchievementMetaDescription(achievement, game));

    // ASSERT
    expect(result.current).toEqual(
      'Easy achievement [5 points], won by 12,345 players - Popular Game for NES',
    );
  });
});
