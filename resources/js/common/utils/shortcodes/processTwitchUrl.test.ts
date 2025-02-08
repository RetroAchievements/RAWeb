import { processTwitchUrl } from './processTwitchUrl';

describe('Util: processTwitchUrl', () => {
  it('is defined', () => {
    // ASSERT
    expect(processTwitchUrl).toBeDefined();
  });

  it('given an invalid URL, returns null', () => {
    // ARRANGE
    const invalidUrls = [
      'https://example.com/video',
      '',
      'not a url at all',
      'twitch',
      'twitch.tv',
      'https://twitch.tv/',
      'https://www.twitch.tv/somechannel',
    ];

    // ASSERT
    for (const url of invalidUrls) {
      expect(processTwitchUrl(url)).toEqual(null);
    }
  });

  it('given a standard video URL, returns the video details', () => {
    // ARRANGE
    const url = 'https://www.twitch.tv/videos/12345';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-video',
      videoId: '12345',
      params: {},
    });
  });

  it('given a channel video URL, returns the video details', () => {
    // ARRANGE
    const url = 'https://www.twitch.tv/user/v/12345';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-video',
      videoId: '12345',
      params: {},
    });
  });

  it('given a video URL without protocol, still processes it correctly', () => {
    // ARRANGE
    const url = 'twitch.tv/videos/12345';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-video',
      videoId: '12345',
      params: {},
    });
  });

  it('given a collection URL, returns the collection details', () => {
    // ARRANGE
    const url = 'https://www.twitch.tv/collections/aabbccdd';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-collection',
      videoId: 'aabbccdd',
      params: {},
    });
  });

  it('given a collection URL without protocol, still processes it correctly', () => {
    // ARRANGE
    const url = 'twitch.tv/collections/aabbccdd';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-collection',
      videoId: 'aabbccdd',
      params: {},
    });
  });

  it('given a clip URL, returns the clip details', () => {
    // ARRANGE
    const url = 'https://clips.twitch.tv/aabbccdd';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-clip',
      videoId: 'aabbccdd',
      params: {},
    });
  });

  it('given a clip URL without protocol, still processes it correctly', () => {
    // ARRANGE
    const url = 'clips.twitch.tv/aabbccdd';

    // ACT
    const result = processTwitchUrl(url);

    // ASSERT
    expect(result).toEqual({
      type: 'twitch-clip',
      videoId: 'aabbccdd',
      params: {},
    });
  });
});
