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

  it('normalizes escaped newlines to actual newlines', () => {
    // ARRANGE
    const input = 'first line↵\nsecond line';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('first line\nsecond line');
  });

  it('handles mixed escaped and actual newlines', () => {
    // ARRANGE
    const input = 'first line↵\nsecond line\nthird line↵\nfourth line';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('first line\nsecond line\nthird line\nfourth line');
  });

  it('normalizes line endings when content includes shortcodes', () => {
    // ARRANGE
    const input =
      'Check out https://retroachievements.org/user/ScottAdams↵\nAnd also↵\nhttps://retroachievements.org/game/1234';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('Check out [user=ScottAdams]\nAnd also\n[game=1234]');
  });

  it('handles carriage returns and different line ending styles', () => {
    // ARRANGE
    const input = 'line1\r\nline2\rline3\nline4';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('line1\nline2\nline3\nline4');
  });

  it('preserves whitespace while normalizing line endings', () => {
    // ARRANGE
    const input = '  indented↵\n  still indented  ↵\n\n  more space  ';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('  indented\n  still indented  \n\n  more space  ');
  });

  it('preserves whitespace while normalizing encoded line endings', () => {
    // ARRANGE
    const input = '  indented\u21B5\n  still indented  \u21B5\n\n  more space  ';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual('  indented\n  still indented  \n\n  more space  ');
  });

  it('preserves external url tag', () => {
    // ARRANGE
    const input = '[url=https://example.com/game/1][/url]';

    // ACT
    const result = preProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toEqual(input);
  });
});
