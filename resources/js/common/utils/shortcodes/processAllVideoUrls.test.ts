import { processAllVideoUrls } from './processAllVideoUrls';
import * as ProcessVideoUrlModule from './processVideoUrl';

describe('Util: processAllVideoUrls', () => {
  it('is defined', () => {
    // ASSERT
    expect(processAllVideoUrls).toBeDefined();
  });

  it('given text with no URLs, returns the original text', () => {
    // ARRANGE
    const text = 'This is some text without any URLs.';

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(text);
  });

  it('given text with non-video URLs, returns the original text unchanged', () => {
    // ARRANGE
    const text = 'Check out https://example.com and https://google.com';

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(text);
  });

  it('given text with a video URL, wraps the URL in video tags', () => {
    // ARRANGE
    const videoUrl = 'https://youtube.com/watch?v=123';
    const text = `Check out this video: ${videoUrl}`;

    vi.spyOn(ProcessVideoUrlModule, 'processVideoUrl').mockReturnValueOnce({
      videoId: '123',
      params: {},
      type: 'youtube',
    });

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(`Check out this video: [video]${videoUrl}[/video]`);
  });

  it('given text with multiple video URLs, wraps each URL in video tags', () => {
    // ARRANGE
    const youtubeUrl = 'https://youtube.com/watch?v=123';
    const twitchUrl = 'https://twitch.tv/videos/456';
    const text = `First video: ${youtubeUrl}\nSecond video: ${twitchUrl}`;

    vi.spyOn(ProcessVideoUrlModule, 'processVideoUrl')
      .mockReturnValueOnce({
        videoId: '123',
        params: {},
        type: 'youtube',
      })
      .mockReturnValueOnce({
        videoId: '456',
        params: {},
        type: 'twitch-video',
      });

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(
      `First video: [video]${youtubeUrl}[/video]\nSecond video: [video]${twitchUrl}[/video]`,
    );
  });

  it('given text with mixed video and non-video URLs, only wraps video URLs', () => {
    // ARRANGE
    const videoUrl = 'https://www.youtube.com/watch?v=0HrfxwZZ-NE';
    const normalUrl = 'https://example.com';
    const text = `Video: ${videoUrl}, Website: ${normalUrl}`;

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(
      'Video: [video]https://www.youtube.com/watch?v=0HrfxwZZ-NE[/video], Website: https://example.com',
    );
  });

  it('does not wrap video links embedded in url tags', () => {
    // ARRANGE
    const body = '[url=https://www.youtube.com/watch?v=NODtRgBxPhw]Longplay[/url]';

    // ACT
    const result = processAllVideoUrls(body);

    // ASSERT
    expect(result).toEqual(body);
    expect(result).not.toContain('[video]');
  });

  it('correctly wraps video URLs inside inline spoiler tags', () => {
    // ARRANGE
    const videoUrl = 'https://www.youtube.com/watch?v=tXa1hjmjS9w';
    const input = `[spoiler]${videoUrl}[/spoiler]`;

    // ACT
    const result = processAllVideoUrls(input);

    // ASSERT
    expect(result).toEqual(`[spoiler][video]${videoUrl}[/video][/spoiler]`);
  });
});
