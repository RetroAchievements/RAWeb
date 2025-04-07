const shortcodeTypes = [
  { type: 'achievement', shortcode: 'ach' },
  { type: 'game', shortcode: 'game' },
  { type: 'hub', shortcode: 'hub' },
  { type: 'event', shortcode: 'event' },
  { type: 'ticket', shortcode: 'ticket' },
  { type: 'user', shortcode: 'user' },
] as const;

const createPatterns = (type: string) => [
  // HTML anchor tags.
  new RegExp(
    `<a [^/>]*retroachievements\\.org/${type}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))[^/>]*\\][^</a>]*</a>`,
    'gi',
  ),

  // BBCode url tags.
  new RegExp(
    `\\[url[^\\]]*retroachievements\\.org/${type}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))[^\\]]*\\][^\\[]*\\[/url\\]`,
    'gi',
  ),

  // Direct production URLs.
  new RegExp(
    `https?://(?:[\\w-]+\\.)?retroachievements\\.org/${type}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))`,
    'gi',
  ),

  // Local development URLs.
  new RegExp(
    `https?://localhost(?::\\d{1,5})?/${type}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))`,
    'gi',
  ),
];

export function preProcessShortcodesInBody(body: string): string {
  // First, normalize any escaped newlines back to actual newlines.
  let result = body.replace(/\u21B5\n/g, '\n');

  // Then, normalize any remaining line endings.
  result = result.replace(/\r\n|\r|\n/g, '\n');

  for (const { type, shortcode } of shortcodeTypes) {
    const patterns = createPatterns(type);
    for (const regex of patterns) {
      result = result.replaceAll(regex, `[${shortcode}=$1]`);
    }
  }

  // Clean up nested shortcodes in url tags.
  // "[url=[user=Scott]]" -> "[user=Scott]"
  result = result.replace(/\[url=\[(\w+=[^\]]+)\]\]/g, '[$1]');

  return result;
}
