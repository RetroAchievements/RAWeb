import { processAllVideoUrls } from './processAllVideoUrls';
import { processVideoUrl } from './processVideoUrl';

vi.mock('./processVideoUrl');

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
    vi.mocked(processVideoUrl).mockReturnValue(null);

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(text);
  });

  it('given text with a video URL, wraps the URL in video tags', () => {
    // ARRANGE
    const videoUrl = 'https://youtube.com/watch?v=123';
    const text = `Check out this video: ${videoUrl}`;

    vi.mocked(processVideoUrl).mockReturnValue({
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

    vi.mocked(processVideoUrl)
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
    const videoUrl = 'https://youtube.com/watch?v=123';
    const normalUrl = 'https://example.com';
    const text = `Video: ${videoUrl}, Website: ${normalUrl}`;

    vi.mocked(processVideoUrl)
      .mockReturnValueOnce({
        videoId: '123',
        params: {},
        type: 'youtube',
      })
      .mockReturnValueOnce(null);

    // ACT
    const result = processAllVideoUrls(text);

    // ASSERT
    expect(result).toEqual(
      'Video: [video]https://youtube.com/watch?v=123[/video], Website: https://example.com',
    );
  });
});
