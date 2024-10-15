import { buildAwardLabelColorClassNames } from './buildAwardLabelColorClassNames';
import { AwardType } from './generatedAppConstants';

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

  it('given there is awardType, but no awardDataExtra, returns null', () => {
    // ACT
    const result = buildAwardLabelColorClassNames(AwardType.Mastery, undefined);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given the award type is a non-game award, always returns null', () => {
    // ACT
    const resultOne = buildAwardLabelColorClassNames(AwardType.PatreonSupporter, 1, 'base');
    const resultTwo = buildAwardLabelColorClassNames(AwardType.CertifiedLegend, 0, 'muted-group');

    // ASSERT
    expect(resultOne).toBeNull();
    expect(resultTwo).toBeNull();
  });

  it('given the consumer is using the "base" variant, returns all correct classes for the various awards', () => {
    // ACT
    const mastery = buildAwardLabelColorClassNames(AwardType.Mastery, 1, 'base');
    const completion = buildAwardLabelColorClassNames(AwardType.Mastery, 0, 'base');
    const gameBeaten = buildAwardLabelColorClassNames(AwardType.GameBeaten, 1, 'base');
    const gameBeatenSoftcore = buildAwardLabelColorClassNames(AwardType.GameBeaten, 0, 'base');

    // ASSERT
    expect(mastery).toEqual('text-[gold] light:text-yellow-600');
    expect(completion).toEqual('text-yellow-600');
    expect(gameBeaten).toEqual('text-zinc-300');
    expect(gameBeatenSoftcore).toEqual('text-zinc-400');
  });

  it('given the consumer is using the "muted-group" variant, returns all correct classes for the various awards', () => {
    // ACT
    const mastery = buildAwardLabelColorClassNames(AwardType.Mastery, 1, 'muted-group');
    const completion = buildAwardLabelColorClassNames(AwardType.Mastery, 0, 'muted-group');
    const gameBeaten = buildAwardLabelColorClassNames(AwardType.GameBeaten, 1, 'muted-group');
    const gameBeatenSoftcore = buildAwardLabelColorClassNames(
      AwardType.GameBeaten,
      0,
      'muted-group',
    );

    // ASSERT
    expect(mastery).toEqual(
      'transition text-muted group-hover:text-[gold] group-hover:light:text-yellow-600',
    );
    expect(completion).toEqual('transition text-muted group-hover:text-yellow-600');
    expect(gameBeaten).toEqual('transition text-muted group-hover:text-zinc-300');
    expect(gameBeatenSoftcore).toEqual('transition text-muted group-hover:text-zinc-400');
  });
});
