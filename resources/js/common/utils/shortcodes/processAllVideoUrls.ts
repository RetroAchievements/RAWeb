import { getIsInsideBbcodeTag } from './getIsInsideBbcodeTag';
import { processVideoUrl } from './processVideoUrl';

/**
 * Converts plain video URLs in text to video shortcodes.
 * Handles both YouTube and Twitch URLs, preserving any parameters.
 */
export function processAllVideoUrls(text: string): string {
  return text.replace(/https?:\/\/[^\s<>]+\b/g, (url, offset) => {
    const videoInfo = processVideoUrl(url);

    if (
      videoInfo &&
      !getIsInsideBbcodeTag(offset, text, 'url') &&
      !getIsInsideBbcodeTag(offset, text, 'video')
    ) {
      return `[video]${url}[/video]`;
    }

    return url;
  });
}
