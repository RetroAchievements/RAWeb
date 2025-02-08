import type { ProcessedVideo } from '@/common/models';

import { convertYouTubeTime } from './convertYouTubeTime';

/**
 * @see http://stackoverflow.com/questions/5830387/how-to-find-all-youtube-video-ids-in-a-string-using-a-regex
 * Enhanced to support timestamp parameters.
 */
export function processYouTubeUrl(url: string): ProcessedVideo | null {
  // This regex pattern matches YouTube URLs with the following structure:
  // (?:https?://)?      # Optional scheme. Either http or https.
  // (?:[0-9A-Z-]+\.)?   # Optional subdomain.
  // (?:                 # Group host alternatives.
  //   youtu\.be/        # Either youtu.be (trailing slash required),
  // | youtube\.com      # or youtube.com followed by
  //   \S*               # Allow anything up to VIDEO_ID,
  //   [^\w\\-\s]        # but char before ID is non-ID char.
  // )                   # End host alternatives.
  // ([\w\-]{11})        # $1: VIDEO_ID is exactly 11 chars.
  // (?=[^\w\-]|$)       # Assert next char is non-ID or EOS.
  // (?!                 # Assert URL is not pre-linked.
  //   (?:               # Group pre-linked alternatives.
  //     [^<>]*>         # Either inside a start tag,
  //   | [^<>]*</a>      # or inside <a> element text contents.
  //   )                 # End recognized pre-linked alts.
  // )                   # End negative lookahead assertion.
  // ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
  const youtubeRegex =
    /(?:https?:\/\/)?(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com\S*[^\w\s-])([\w-]{11})(?=[^\w-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))([?&=+%\w.-]*)/i;
  const matches = url.match(youtubeRegex);

  if (!matches) {
    return null;
  }

  const videoId = matches[1];
  const queryString = matches[2];
  const params: Record<string, string> = {};

  // Parse query parameters.
  if (queryString) {
    const searchParams = new URLSearchParams(queryString);

    for (const [key, value] of searchParams) {
      if (key === 't') {
        // "t" has to be converted to a time compatible with youtube-nocookie.com embeds.
        params['start'] = convertYouTubeTime(value).toString();
      } else {
        params[key] = value;
      }
    }
  }

  return { params, videoId, type: 'youtube' };
}
