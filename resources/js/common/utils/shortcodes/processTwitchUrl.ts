import type { ProcessedVideo } from '@/common/models';

/**
 * Supports three types of Twitch URLs:
 * 1. Video URLs: https://www.twitch.tv/videos/270709956 or https://www.twitch.tv/gamingwithmist/v/40482810
 * 2. Collection URLs: https://www.twitch.tv/collections/cWHCMbAY1xQVDA
 * 3. Clip URLs: https://clips.twitch.tv/AmorphousCautiousLegPanicVis
 */
export function processTwitchUrl(url: string): ProcessedVideo | null {
  // Twitch video regex pattern:
  // (?:https?://)?      # Optional scheme. Either http or https.
  // (?:www.)?           # Optional subdomain.
  // twitch.tv/.*        # Host.
  // (?:videos|[^/]+/v)  # Match either "videos" or "{username}/v"
  // /(\d+)              # Video ID (numeric)
  // (?!                 # Assert URL is not pre-linked.
  //   [?=&+%\w.-]*      # Allow URL (query) remainder.
  //   (?:               # Group pre-linked alternatives.
  //     [^<>]*>         # Either inside a start tag,
  //   | [^<>]*</a>      # or inside <a> element text contents.
  //   )                 # End recognized pre-linked alts.
  // )                   # End negative lookahead assertion.
  // ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
  const videoRegex =
    /(?:https?:\/\/)?(?:www\.)?twitch\.tv\/.*(?:videos|[^/]+\/v)\/(\d+)([?=&+%\w.-]*)/i;

  // Matches URLs like: https://www.twitch.tv/collections/cWHCMbAY1xQVDA.
  const collectionRegex = /(?:https?:\/\/)?(?:www\.)?twitch\.tv\/collections\/([a-z0-9]+)/i;

  // Matches URLs like: https://clips.twitch.tv/AmorphousCautiousLegPanicVis.
  const clipRegex = /(?:https?:\/\/)?clips\.twitch\.tv\/([a-zA-Z0-9-_]+)/i;

  let matches;

  // Check for video URLs.
  matches = url.match(videoRegex);
  if (matches) {
    return {
      type: 'twitch-video',
      videoId: matches[1],
      params: {},
    };
  }

  // Check for collection URLs.
  matches = url.match(collectionRegex);
  if (matches) {
    return {
      type: 'twitch-collection',
      videoId: matches[1],
      params: {},
    };
  }

  // Check for clip URLs.
  matches = url.match(clipRegex);
  if (matches) {
    return {
      type: 'twitch-clip',
      videoId: matches[1],
      params: {},
    };
  }

  return null;
}
