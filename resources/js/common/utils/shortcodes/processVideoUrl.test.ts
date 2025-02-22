import type { ProcessedVideo } from '@/common/models';

import { processTwitchUrl } from './processTwitchUrl';
import { processVideoUrl } from './processVideoUrl';
import { processYouTubeUrl } from './processYouTubeUrl';

vi.mock('./processYouTubeUrl');
vi.mock('./processTwitchUrl');

describe('Util: processVideoUrl', () => {
  it('is defined', () => {
    // ASSERT
    expect(processVideoUrl).toBeDefined();
  });

  it('given an invalid URL, returns null', () => {
    // ARRANGE
    vi.mocked(processYouTubeUrl).mockReturnValue(null);
    vi.mocked(processTwitchUrl).mockReturnValue(null);

    // ACT
    const result = processVideoUrl('not a valid url');

    // ASSERT
    expect(result).toEqual(null);
  });

  it('given a YouTube URL, returns the processed YouTube video details', () => {
    // ARRANGE
    const youtubeResult: ProcessedVideo = {
      videoId: '123',
      params: {},
      type: 'youtube',
    };
    vi.mocked(processYouTubeUrl).mockReturnValue(youtubeResult);

    // ACT
    const result = processVideoUrl('some-youtube-url');

    // ASSERT
    expect(result).toEqual(youtubeResult);
    expect(processTwitchUrl).not.toHaveBeenCalled();
  });

  it('given a Twitch URL, returns the processed Twitch video details', () => {
    // ARRANGE
    const twitchResult: ProcessedVideo = {
      videoId: '456',
      params: {},
      type: 'twitch-video',
    };
    vi.mocked(processYouTubeUrl).mockReturnValue(null);
    vi.mocked(processTwitchUrl).mockReturnValue(twitchResult);

    // ACT
    const result = processVideoUrl('some-twitch-url');

    // ASSERT
    expect(result).toEqual(twitchResult);
  });

  it('given a YouTube URL, does not check if it is a Twitch URL', () => {
    // ARRANGE
    const youtubeResult: ProcessedVideo = {
      videoId: '123',
      params: {},
      type: 'youtube',
    };
    vi.mocked(processYouTubeUrl).mockReturnValue(youtubeResult);

    // ACT
    processVideoUrl('some-youtube-url');

    // ASSERT
    expect(processTwitchUrl).not.toHaveBeenCalled();
  });
});
