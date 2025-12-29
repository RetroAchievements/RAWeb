import { buildAwardLabelColorClassNames } from './buildAwardLabelColorClassNames';

describe('Util: buildAwardLabelColorClassNames', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildAwardLabelColorClassNames).toBeDefined();
  });

  it('given there are no award details, returns null', () => {
    // ACT
    const result = buildAwardLabelColorClassNames(undefined, undefined);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given there is awardType, but no awardTier, returns null', () => {
    // ACT
    const result = buildAwardLabelColorClassNames('mastery', undefined);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given the award type is a non-game award, always returns null', () => {
    // ACT
    const resultOne = buildAwardLabelColorClassNames('patreon_supporter', 1, 'base');
    const resultTwo = buildAwardLabelColorClassNames('certified_legend', 0, 'muted-group');

    // ASSERT
    expect(resultOne).toBeNull();
    expect(resultTwo).toBeNull();
  });

  it('given the consumer is using the "base" variant, returns all correct classes for the various awards', () => {
    // ACT
    const mastery = buildAwardLabelColorClassNames('mastery', 1, 'base');
    const completion = buildAwardLabelColorClassNames('mastery', 0, 'base');
    const gameBeaten = buildAwardLabelColorClassNames('game_beaten', 1, 'base');
    const gameBeatenSoftcore = buildAwardLabelColorClassNames('game_beaten', 0, 'base');

    // ASSERT
    expect(mastery).toEqual('text-[gold] light:text-yellow-600');
    expect(completion).toEqual('text-yellow-600');
    expect(gameBeaten).toEqual('text-zinc-300');
    expect(gameBeatenSoftcore).toEqual('text-zinc-400');
  });

  it('given the consumer is using the "muted-group" variant, returns all correct classes for the various awards', () => {
    // ACT
    const mastery = buildAwardLabelColorClassNames('mastery', 1, 'muted-group');
    const completion = buildAwardLabelColorClassNames('mastery', 0, 'muted-group');
    const gameBeaten = buildAwardLabelColorClassNames('game_beaten', 1, 'muted-group');
    const gameBeatenSoftcore = buildAwardLabelColorClassNames('game_beaten', 0, 'muted-group');

    // ASSERT
    expect(mastery).toEqual(
      'transition text-muted group-hover:text-[gold] group-hover:light:text-yellow-600',
    );
    expect(completion).toEqual('transition text-muted group-hover:text-yellow-600');
    expect(gameBeaten).toEqual('transition text-muted group-hover:text-zinc-300');
    expect(gameBeatenSoftcore).toEqual('transition text-muted group-hover:text-zinc-400');
  });
});
