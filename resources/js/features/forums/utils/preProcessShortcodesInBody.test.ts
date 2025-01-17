import { preProcessShortcodesInBody } from './preProcessShortcodesInBody';

describe('Util: preProcessShortcodesInBody', () => {
  it('is defined', () => {
    // ASSERT
    expect(preProcessShortcodesInBody).toBeDefined();
  });

  it('given a user profile link, converts it to a user shortcode', () => {
    // ARRANGE
    const input = 'https://retroachievements.org/user/ScottAdams';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('[user=ScottAdams]');
  });

  it('given a game BBCode url tag, converts it to a game shortcode', () => {
    // ARRANGE
    const input = '[url=https://retroachievements.org/game/1234]Cool Game[/url]';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('[game=1234]');
  });

  it('given an achievement direct production URL, converts it to an achievement shortcode', () => {
    // ARRANGE
    const input = 'Check out https://retroachievements.org/achievement/12345';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('Check out [ach=12345]');
  });

  it('given a local development URL, converts it to the appropriate shortcode', () => {
    // ARRANGE
    const input = 'Dev link: http://localhost:3000/ticket/67890';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('Dev link: [ticket=67890]');
  });

  it('given nested shortcodes in url tags, removes the url tag', () => {
    // ARRANGE
    const input = '[url=[user=ScottAdams]]';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('[user=ScottAdams]');
  });

  it('given multiple different shortcode types in a single string, converts all of them', () => {
    // ARRANGE
    const input =
      'Check out https://retroachievements.org/user/ScottAdams and https://retroachievements.org/game/1234';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('Check out [user=ScottAdams] and [game=1234]');
  });

  it('preserves text that does not match shortcode patterns', () => {
    // ARRANGE
    const input = 'This is a regular text without any special links.';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual(input);
  });
});
