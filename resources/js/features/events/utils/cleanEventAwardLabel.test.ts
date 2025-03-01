import { createGame, createRaEvent } from '@/test/factories';

import { cleanEventAwardLabel } from './cleanEventAwardLabel';

describe('Util: cleanEventAwardLabel', () => {
  it('is defined', () => {
    // ASSERT
    expect(cleanEventAwardLabel).toBeDefined();
  });

  it('given the award label is identical to the event title, returns it untouched', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Achievement of the Week 2025' });
    const event = createRaEvent({ legacyGame });

    const awardLabel = 'Achievement of the Week 2025';

    // ACT
    const result = cleanEventAwardLabel(awardLabel, event);

    // ASSERT
    expect(result).toEqual(awardLabel);
  });

  it('given the award label starts with the event title, removes the title prefix', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Achievement of the Week 2025' });
    const event = createRaEvent({ legacyGame });

    const awardLabel = 'Achievement of the Week 2025 - Bronze';

    // ACT
    const result = cleanEventAwardLabel(awardLabel, event);

    // ASSERT
    expect(result).toEqual('Bronze');
  });

  it('given the award label starts with the event title, removes the title prefix regardless of delimiter used', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Achievement of the Week 2025' });
    const event = createRaEvent({ legacyGame });

    const awardLabel = 'Achievement of the Week 2025: Bronze';

    // ACT
    const result = cleanEventAwardLabel(awardLabel, event);

    // ASSERT
    expect(result).toEqual('Bronze');
  });

  it('given the award label does not start with the event title, returns the original label', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Super Mario 64' });
    const event = createRaEvent({ legacyGame });
    const awardLabel = 'Complete All Levels';

    // ACT
    const result = cleanEventAwardLabel(awardLabel, event);

    // ASSERT
    expect(result).toEqual('Complete All Levels');
  });

  it('given the award label has multiple delimiters after the title, removes them all', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Super Mario 64' });
    const event = createRaEvent({ legacyGame });
    const awardLabel = 'Super Mario 64 - : - 120 Stars';

    // ACT
    const result = cleanEventAwardLabel(awardLabel, event);

    // ASSERT
    expect(result).toEqual('120 Stars');
  });
});
