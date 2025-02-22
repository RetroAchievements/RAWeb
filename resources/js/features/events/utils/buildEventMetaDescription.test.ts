import { createEventAchievement, createRaEvent } from '@/test/factories';

import { buildEventMetaDescription } from './buildEventMetaDescription';

describe('Util: buildEventMetaDescription', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: [],
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('given an evergreen event, returns a message about non time limited achievements', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'evergreen',
      eventAchievements: [createEventAchievement(), createEventAchievement()],
      activeFrom: '2023-01-01',
      activeThrough: '2023-12-31',
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('A non time limited event containing 2 achievements.');
  });

  it('given an active event with an end date, returns a message with the end date', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: [createEventAchievement()],
      activeFrom: '2023-01-01',
      activeThrough: '2023-12-31',
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('An ongoing event until Dec 31, 2023 featuring 1 achievement.');
  });

  it('given an active event without an end date, returns a message without a date reference', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: [createEventAchievement()],
      activeFrom: '2023-01-01',
      activeThrough: null,
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('An ongoing event featuring 1 achievement.');
  });

  it('given a concluded event with a date range, returns a message with the full date range', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'concluded',
      eventAchievements: [
        createEventAchievement(),
        createEventAchievement(),
        createEventAchievement(),
      ],
      activeFrom: '2023-01-01',
      activeThrough: '2023-12-31',
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual(
      'A past event that ran from Jan 1, 2023 to Dec 31, 2023 featuring 3 achievements.',
    );
  });

  it('given a concluded event without dates, returns a message without date references', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'concluded',
      eventAchievements: [
        createEventAchievement(),
        createEventAchievement(),
        createEventAchievement(),
      ],
      activeFrom: null,
      activeThrough: null,
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('A past event featuring 3 achievements.');
  });

  it('given a concluded event without dates and only 1 achievement, returns a message without date references', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'concluded',
      eventAchievements: [createEventAchievement()],
      activeFrom: null,
      activeThrough: null,
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('A past event featuring 1 achievement.');
  });

  it('given an event without achievements, handles zero achievements count correctly', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: undefined,
      activeFrom: '2023-01-01',
      activeThrough: null,
    });

    // ACT
    const result = buildEventMetaDescription(event);

    // ASSERT
    expect(result).toEqual('An ongoing event featuring 0 achievements.');
  });
});
