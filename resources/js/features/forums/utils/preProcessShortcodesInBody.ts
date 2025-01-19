const shortcodeTypes = [
  { type: 'achievement', shortcode: 'ach' },
  { type: 'game', shortcode: 'game' },
  { type: 'hub', shortcode: 'hub' },
  { type: 'ticket', shortcode: 'ticket' },
  { type: 'user', shortcode: 'user' },
] as const;

const createPatterns = (type: string) => [
  // HTML anchor tags.
  new RegExp(
    `<a [^/>]*retroachievements\\.org/${type}/(\\w{1,20})(/?(?![\\w/?]))[^/>]*\\][^</a>]*</a>`,
    'gi',
  ),

  // BBCode url tags.
  new RegExp(
    `\\[url[^\\]]*retroachievements\\.org/${type}/(\\w{1,20})(/?(?![\\w/?]))[^\\]]*\\][^\\[]*\\[/url\\]`,
    'gi',
  ),
  new RegExp(`\\[url[^\\]]*?${type}/(\\w{1,20})(/?(?![\\w/?])).*?\\[/url\\]`, 'gi'),

  // Direct production URLs.
  new RegExp(
    `https?://(?:[\\w-]+\\.)?retroachievements\\.org/${type}/(\\w{1,20})(/?(?![\\w/?]))`,
    'gi',
  ),

  // Local development URLs.
  new RegExp(`https?://localhost(?::\\d{1,5})?/${type}/(\\w{1,20})(/?(?![\\w/?]))`, 'gi'),
];

export function preProcessShortcodesInBody(body: string): string {
  let result = body;

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
