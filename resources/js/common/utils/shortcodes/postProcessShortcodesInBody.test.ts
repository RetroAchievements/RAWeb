import { postProcessShortcodesInBody } from './postProcessShortcodesInBody';
import { processAllVideoUrls } from './processAllVideoUrls';

vi.mock('./processAllVideoUrls');

describe('Util: postProcessShortcodesInBody', () => {
  beforeEach(() => {
    vi.mocked(processAllVideoUrls).mockImplementation((text) => text);
  });

  it('is defined', () => {
    // ASSERT
    expect(postProcessShortcodesInBody).toBeDefined();
  });

  it('given a body with existing shortcodes, does not modify them', () => {
    // ARRANGE
    const input = 'Check [b]bold[/b] and [i]italic[/i] text';

    // ACT
    const result = postProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toBe(input);
  });

  it('given a self-closing url tag, expands it to include the URL as content', () => {
    // ARRANGE
    const body = '[url=https://example.com]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[url="https://example.com"]https://example.com[/url]');
  });

  it('given a plain text with URLs outside of tags, wraps non-video URLs in [url] tags', () => {
    // ARRANGE
    const input = 'Check out this site: https://example.com and this one http://test.com';

    // ACT
    const result = postProcessShortcodesInBody(input);

    // ASSERT
    expect(result).toBe(
      'Check out this site: [url]https://example.com[/url] and this one [url]http://test.com[/url]',
    );
  });

  it('given a self-closing url tag with single quotes, handles it correctly', () => {
    // ARRANGE
    const body = "[url='https://example.com']";

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[url="https://example.com"]https://example.com[/url]');
  });

  it('given a self-closing url tag without quotes, handles it correctly', () => {
    // ARRANGE
    const body = '[url=https://example.com]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[url="https://example.com"]https://example.com[/url]');
  });

  it('given a properly closed url tag, does not modify it', () => {
    // ARRANGE
    const body = '[url="https://example.com"]Click here[/url]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual(body);
  });

  it('given a self-closing tag that is not a url tag, expands it to include the value as content', () => {
    // ARRANGE
    const body = '[color="#ff0000"]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[color]#ff0000[/color]');
  });

  it('given multiple shortcodes, processes them all correctly', () => {
    // ARRANGE
    const body = '[url="https://example.com"] [color="#ff0000"] [size=12]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual(
      '[url="https://example.com"]https://example.com[/url] [color]#ff0000[/color] [size]12[/size]',
    );
  });

  it('given text with URLs, processes them for video embeds', () => {
    // ARRANGE
    const body = 'Check out https://youtube.com/watch?v=123';
    vi.mocked(processAllVideoUrls).mockReturnValue(
      'Check out [video]https://youtube.com/watch?v=123[/video]',
    );

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('Check out [video]https://youtube.com/watch?v=123[/video]');
  });

  it('does not double wrap url tags', () => {
    // ARRANGE
    const body = '[url]https://example.com[/url]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).not.toContain('[url][url]');
    expect(result).toEqual('[url]https://example.com[/url]');
  });

  it('does not nest img links within url tags', () => {
    // ARRANGE
    const body = '[img]https://i.imgur.com/ov30jeD.jpg[/img]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).not.toContain('[img][url]');
    expect(result).toEqual('[img]https://i.imgur.com/ov30jeD.jpg[/img]');
  });

  it('can handle complex inference even when mixed with self-closing url shortcodes', () => {
    // ARRANGE
    const body =
      '[url=https://google.com]my link[/url] [url=google.com] [url=https://google.com] asdf [user=WCopeland] https://retroachievements.org/user/WCopeland/progress https://retroachievements.org';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual(
      '[url=https://google.com]my link[/url] [url="google.com"]google.com[/url] [url="https://google.com"]https://google.com[/url] asdf [user]WCopeland[/user] [url]https://retroachievements.org/user/WCopeland/progress[/url] [url]https://retroachievements.org[/url]',
    );
  });

  it('handles URLs inside nested BBCode tags correctly', () => {
    // ARRANGE
    const body = '[spoiler][b]Check this out: https://example.com[/b][/spoiler]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual(
      '[spoiler][b]Check this out: [url]https://example.com[/url][/b][/spoiler]',
    );
  });

  it('completely removes empty self-closing tags', () => {
    // ARRANGE
    const body = '[url=""]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('');
  });

  it('strips body content from img tags', () => {
    // ARRANGE
    const body = '[img=https://i.imgur.com/ov30jeD.jpeg]body[/img]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[img=https://i.imgur.com/ov30jeD.jpeg][/img]');
  });

  it('sanitizes self-closing img tags', () => {
    // ARRANGE
    const body = '[spoiler][img=https://i.imgur.com/ov30jeD.jpeg][/spoiler]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[spoiler][img]https://i.imgur.com/ov30jeD.jpeg[/img][/spoiler]');
  });

  it('preserves placeholder achievement tags', () => {
    // ARRANGE
    const body = '[ach=]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[text]{ach=}[/text]');
  });

  it('preserves placeholder url tags with content', () => {
    // ARRANGE
    const body = '[url=]Link[/url]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[text]{url=}Link{/url}[/text]');
  });

  it('handles multiple placeholder tags', () => {
    // ARRANGE
    const body = '[ach=][ach=][game=]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[text]{ach=}[/text][text]{ach=}[/text][text]{game=}[/text]');
  });

  it('preserves placeholder tags inside spoiler tags', () => {
    // ARRANGE
    const body = '[spoiler][ach=][url=]Link[/url][/spoiler]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[spoiler][text]{ach=}[/text][text]{url=}Link{/url}[/text][/spoiler]');
  });

  it('handles both placeholder and valid tags in the same input', () => {
    // ARRANGE
    const body = '[ach=]\n[ach=123][url=]Link[/url]\n[url=https://google.com]My Link[/url]';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual(
      '[text]{ach=}[/text]\n' +
        '[ach]123[/ach][text]{url=}Link{/url}[/text]\n' +
        '[url=https://google.com]My Link[/url]',
    );
  });

  it('handles empty quoted tags differently from placeholder tags', () => {
    // ARRANGE
    const body = '[url=""][ach=][url=\'\']';

    // ACT
    const result = postProcessShortcodesInBody(body);

    // ASSERT
    expect(result).toEqual('[text]{ach=}[/text]');
  });
});
