import { processVideoUrl } from './processVideoUrl';

/**
 * Converts plain video URLs in text to video shortcodes.
 * Handles both YouTube and Twitch URLs, preserving any parameters.
 */
export function processAllVideoUrls(text: string): string {
  const urls = text.match(/https?:\/\/[^\s<>]+\b/g) ?? [];

  let processedText = text;
  for (const url of urls) {
    const videoInfo = processVideoUrl(url);
    if (videoInfo) {
      processedText = processedText.replace(url, `[video]${url}[/video]`);
    }
  }

  return processedText;
}
