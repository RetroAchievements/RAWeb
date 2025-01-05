import { processYouTubeUrl } from './processYouTubeUrl';

describe('Util: processYouTubeUrl', () => {
  it('is defined', () => {
    // ASSERT
    expect(processYouTubeUrl).toBeDefined();
  });

  it('given an invalid URL, returns null', () => {
    // ARRANGE
    const invalidUrls = [
      'https://example.com/video',
      '',
      'not a url at all',
      'youtube',
      'youtube.com',
      'https://youtube.com/',
    ];

    // ASSERT
    for (const url of invalidUrls) {
      expect(processYouTubeUrl(url)).toEqual(null);
    }
  });

  it('given a standard youtube.com URL, returns the video details', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {},
      type: 'youtube',
    });
  });

  it('given a youtu.be short URL, returns the video details', () => {
    // ARRANGE
    const url = 'https://youtu.be/dQw4w9WgXcQ';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {},
      type: 'youtube',
    });
  });

  it('given a URL with a timestamp in seconds, converts it to the proper format', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {
        start: '30',
      },
      type: 'youtube',
    });
  });

  it('given a URL with other query parameters, preserves them in the result', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=share&ab_channel=user';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {
        feature: 'share',
        ab_channel: 'user',
      },
      type: 'youtube',
    });
  });

  it('given a URL without protocol, still processes it correctly', () => {
    // ARRANGE
    const url = 'youtube.com/watch?v=dQw4w9WgXcQ';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {},
      type: 'youtube',
    });
  });

  it('given a URL with a timestamp in minutes and seconds format, converts it correctly', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m30s';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {
        start: '90',
      },
      type: 'youtube',
    });
  });

  it('given a URL with a query string missing the ? prefix, still processes parameters correctly', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/watch&v=dQw4w9WgXcQ&feature=share';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {
        feature: 'share',
      },
      type: 'youtube',
    });
  });

  it('given a URL with an embedded video ID and additional parameters, processes parameters correctly', () => {
    // ARRANGE
    const url = 'https://www.youtube.com/embed/dQw4w9WgXcQ&controls=0';

    // ACT
    const result = processYouTubeUrl(url);

    // ASSERT
    expect(result).toEqual({
      videoId: 'dQw4w9WgXcQ',
      params: {
        controls: '0',
      },
      type: 'youtube',
    });
  });
});
