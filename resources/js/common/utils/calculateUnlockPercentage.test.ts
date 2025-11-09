import { calculateUnlockPercentage } from './calculateUnlockPercentage';

describe('Util: calculateUnlockPercentage', () => {
  it('is defined', () => {
    // ASSERT
    expect(calculateUnlockPercentage).toBeDefined();
  });

  it('given hardcore stats are prioritized and we have a valid player total, calculates percentage from hardcore unlocks', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = true;
    const unlocksHardcoreTotal = 50;
    const playersTotal = 200; // !! 50/200 = 0.25

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      10.5,
    );

    // ASSERT
    expect(result).toEqual(0.25);
  });

  it('given hardcore stats are prioritized but players total is zero, returns zero', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = true;
    const unlocksHardcoreTotal = 50;
    const playersTotal = 0; // !!

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      10.5,
    );

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given hardcore stats are prioritized but players total is null, returns zero', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = true;
    const unlocksHardcoreTotal = 50;
    const playersTotal = null; // !!

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      10.5,
    );

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given hardcore stats are not prioritized and a default unlock percentage is provided, returns the default percentage', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = false;
    const unlocksHardcoreTotal = 50;
    const playersTotal = 200;
    const defaultUnlockPercentage = 15.75; // !! should return this value

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      defaultUnlockPercentage,
    );

    // ASSERT
    expect(result).toEqual(defaultUnlockPercentage);
  });

  it('given hardcore stats are not prioritized and the default unlock percentage is null, returns zero', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = false;
    const unlocksHardcoreTotal = 50;
    const playersTotal = 200;
    const defaultUnlockPercentage = null; // !!

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      defaultUnlockPercentage,
    );

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given hardcore stats are not prioritized and default unlock percentage is undefined, returns zero', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = false;
    const unlocksHardcoreTotal = 50;
    const playersTotal = 200;
    const defaultUnlockPercentage = undefined; // !!

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      defaultUnlockPercentage,
    );

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given hardcore stats are prioritized and there are zero hardcore unlocks, returns zero', () => {
    // ARRANGE
    const shouldPrioritizeHardcoreStats = true;
    const unlocksHardcoreTotal = 0; // !!
    const playersTotal = 200;

    // ACT
    const result = calculateUnlockPercentage(
      shouldPrioritizeHardcoreStats,
      unlocksHardcoreTotal,
      playersTotal,
      10.5,
    );

    // ASSERT
    expect(result).toEqual(0);
  });
});
