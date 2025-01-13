import type { ProcessedVideo } from '../models';
import { processTwitchUrl } from './processTwitchUrl';
import { processYouTubeUrl } from './processYouTubeUrl';

/**
 * Process any video URL, supporting both YouTube and Twitch formats.
 * Returns null if the URL doesn't match any supported video format.
 */
export function processVideoUrl(url: string): ProcessedVideo | null {
  // First, try YouTube.
  const youtubeResult = processYouTubeUrl(url);
  if (youtubeResult) return youtubeResult;

  // Next, try Twitch.
  const twitchResult = processTwitchUrl(url);
  if (twitchResult) return twitchResult;

  return null;
}
