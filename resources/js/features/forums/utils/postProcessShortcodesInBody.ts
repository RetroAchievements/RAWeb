import { processAllVideoUrls } from './processAllVideoUrls';

export function postProcessShortcodesInBody(body: string): string {
  let result = body;

  // Helper to check if we're inside a specific BBCode tag.
  const isInsideTag = (index: number, text: string, tagName: string): boolean => {
    const beforeText = text.slice(0, index);
    const openTagPattern = new RegExp(`\\[${tagName}[\\]=]`, 'g');
    const closeTagPattern = new RegExp(`\\[\\/${tagName}\\]`, 'g');

    const openMatches = [...beforeText.matchAll(openTagPattern)];
    const closeMatches = [...beforeText.matchAll(closeTagPattern)];

    return openMatches.length > closeMatches.length;
  };

  // First, remove any empty self-closing tags.
  result = result.replace(/\[(\w+)=["']?["']?\]/g, '');

  // Strip body content from img tags while preserving the img=url format.
  result = result.replace(/\[img=([^\]]+)\].*?\[\/img\]/g, '[img=$1][/img]');

  // Handle self-closing url tags.
  result = result.replace(
    /\[url=["']?([^"'\]]+)["']?\](?!\s*[^\n\[]*\[\/url\])/g,
    '[url="$1"]$1[/url]',
  );

  // Then, handle all other [tag=value] formats, excluding url tags.
  result = result.replace(
    /\[(?!url\b)(?!img\b)(\w+)=["']?([^"'\]]+)["']?\]/g,
    (_, tag, value) => `[${tag}]${value}[/${tag}]`,
  );

  // Finally, wrap bare URLs in [url] tags, but only if they're not inside any tags.
  const urlPattern =
    /https?:\/\/(?!(?:(?:www\.)?(?:youtube\.com|youtu\.be|twitch\.tv)|clips\.twitch\.tv))[\w-]+(?:\.[\w-]+)+[^\s[\]()]*/gi;
  let lastIndex = 0;
  let newResult = '';

  // eslint-disable-next-line no-constant-condition -- this is fine
  while (true) {
    const match = urlPattern.exec(result);
    if (!match) break;

    const matchText = match[0];
    const matchIndex = match.index;

    // Add text before this match.
    newResult += result.slice(lastIndex, matchIndex);

    // Only wrap if not inside url or img tags.
    if (!isInsideTag(matchIndex, result, 'url') && !isInsideTag(matchIndex, result, 'img')) {
      newResult += `[url]${matchText}[/url]`;
    } else {
      newResult += matchText;
    }

    lastIndex = matchIndex + matchText.length;
    urlPattern.lastIndex = lastIndex;
  }

  // Add remaining text.
  newResult += result.slice(lastIndex);
  result = newResult;

  result = processAllVideoUrls(result);

  return result;
}
