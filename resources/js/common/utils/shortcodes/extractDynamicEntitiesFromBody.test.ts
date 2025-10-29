import { extractDynamicEntitiesFromBody } from './extractDynamicEntitiesFromBody';

describe('Util: extractDynamicEntitiesFromBody', () => {
  it('is defined', () => {
    // ASSERT
    expect(extractDynamicEntitiesFromBody).toBeDefined();
  });

  it('given the input contains user shortcodes, extracts and dedupes all usernames', () => {
    // ARRANGE
    const input = 'Hello [user=Jamiras] and [user=Scott] and [user=Jamiras] again';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.usernames).toEqual(['Jamiras', 'Scott']);
  });

  it('given the input contains ticket shortcodes, extracts and dedupes all ticket IDs', () => {
    // ARRANGE
    const input = 'Check tickets [ticket=123] and [ticket=456] and [ticket=123].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.ticketIds).toEqual([123, 456]);
  });

  it('given the input contains achievement shortcodes, extracts all achievement IDs', () => {
    // ARRANGE
    const input = 'I earned [ach=9] and [ach=14402]!';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.achievementIds).toEqual([9, 14402]);
  });

  it('given the input contains game shortcodes, extracts and dedupes all game IDs', () => {
    // ARRANGE
    const input = 'I like to play [game=1] and [game=14402] and [game=1].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.gameIds).toEqual([1, 14402]);
  });

  it('given the input contains hub shortcodes, extracts and dedupes all hub IDs', () => {
    // ARRANGE
    const input = 'I like to visit [hub=1] and [hub=2] and [hub=1].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.hubIds).toEqual([1, 2]);
  });

  it('given the input contains event shortcodes, extracts and dedupes all event IDs', () => {
    // ARRANGE
    const input = 'I play in [event=1] and [event=2] and [event=1].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.eventIds).toEqual([1, 2]);
  });

  it('given the input contains invalid numeric IDs, ignores them', () => {
    // ARRANGE
    const input = '[ticket=abc] [ach=def] [game=xyz]';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.ticketIds).toEqual([]);
    expect(result.achievementIds).toEqual([]);
    expect(result.gameIds).toEqual([]);
  });

  it('given the input contains multiple types of shortcodes, extracts all entities correctly', () => {
    // ARRANGE
    const input = '[user=xelnia] completed [ach=9] in [game=1] and created [ticket=12345].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.usernames).toEqual(['xelnia']);
    expect(result.achievementIds).toEqual([9]);
    expect(result.gameIds).toEqual([1]);
    expect(result.ticketIds).toEqual([12345]);
  });

  it('given the input contains game shortcodes with a set parameter and "?" separator, extracts set IDs instead of game IDs', () => {
    // ARRANGE
    const input = 'Check out [game=1?set=9534]';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.setIds).toEqual([9534]);
    expect(result.gameIds).toEqual([]);
  });

  it('given the input contains game shortcodes with a set parameter and " " separator, extracts set IDs instead of game IDs', () => {
    // ARRANGE
    const input = 'Check out [game=1 set=9534]';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.setIds).toEqual([9534]);
    expect(result.gameIds).toEqual([]);
  });

  it('given the input contains mixed game shortcodes (with and without sets), extracts both correctly', () => {
    // ARRANGE
    const input = 'Play [game=668] or try [game=668?set=8659]';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.gameIds).toEqual([668]);
    expect(result.setIds).toEqual([8659]);
  });

  it('given the input contains multiple game shortcodes with sets, deduplicates set IDs', () => {
    // ARRANGE
    const input = '[game=1?set=9534] and [game=2?set=9534] and [game=3?set=7777]';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.setIds).toEqual([9534, 7777]);
    expect(result.gameIds).toEqual([]);
  });

  it('given the input contains a bunch of stuff including game codes with set IDs, extracts everything correctly', () => {
    // ARRANGE
    const input =
      '[user=xelnia] earned [ach=9] in [game=1] and [game=668?set=8659] with [ticket=123].';

    // ACT
    const result = extractDynamicEntitiesFromBody(input);

    // ASSERT
    expect(result.usernames).toEqual(['xelnia']);
    expect(result.achievementIds).toEqual([9]);
    expect(result.gameIds).toEqual([1]);
    expect(result.setIds).toEqual([8659]);
    expect(result.ticketIds).toEqual([123]);
  });
});
