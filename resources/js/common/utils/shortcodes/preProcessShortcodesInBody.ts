const shortcodeTypes = [
  { type: 'achievement', shortcode: 'ach' },
  { type: 'game', shortcode: 'game' },
  { type: 'hub', shortcode: 'hub' },
  { type: 'event', shortcode: 'event' },
  { type: 'ticket', shortcode: 'ticket' },
  { type: 'user', shortcode: 'user' },
] as const;

const createPatterns = (type: string) => {
  // For games, match both /game/ and /game2/ URLs.
  const pathPattern = type === 'game' ? 'game2?' : type;

  const patterns = [
    // HTML anchor tags.
    new RegExp(
      `<a [^/>]*retroachievements\\.org/${pathPattern}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))[^/>]*\\][^</a>]*</a>`,
      'gi',
    ),

    // BBCode url tags.
    new RegExp(
      `\\[url[^\\]]*retroachievements\\.org/${pathPattern}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))[^\\]]*\\][^\\[]*\\[/url\\]`,
      'gi',
    ),

    // Direct production URLs without query params.
    new RegExp(
      `https?://(?:[\\w-]+\\.)?retroachievements\\.org/${pathPattern}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))`,
      'gi',
    ),

    // Local development URLs without query params.
    new RegExp(
      `https?://localhost(?::\\d{1,5})?/${pathPattern}/(\\w{1,20})(?:-[^\\s"'<>]*)?(/?(?![\\w/?]))`,
      'gi',
    ),
  ];

  // For games specifically, also handle URLs with ?set= query parameter.
  if (type === 'game') {
    patterns.unshift(
      // Production URLs with a ?set= parameter.
      new RegExp(
        `https?://(?:[\\w-]+\\.)?retroachievements\\.org/game2?/(\\w{1,20})(?:-[^\\s"'<>]*)?(?:/)?\\?set=(\\d+)`,
        'gi',
      ),

      // Local development URLs with a ?set= parameter.
      new RegExp(
        `https?://localhost(?::\\d{1,5})?/game2?/(\\w{1,20})(?:-[^\\s"'<>]*)?(?:/)?\\?set=(\\d+)`,
        'gi',
      ),
    );
  }

  return patterns;
};

export function preProcessShortcodesInBody(body: string): string {
  // First, normalize any escaped newlines back to actual newlines.
  let result = body.replace(/\u21B5\n/g, '\n');

  // Then, normalize any remaining line endings.
  result = result.replace(/\r\n|\r|\n/g, '\n');

  for (const { type, shortcode } of shortcodeTypes) {
    const patterns = createPatterns(type);
    for (let i = 0; i < patterns.length; i++) {
      const regex = patterns[i];

      // Special handling for game URLs with a ?set= parameter (first two patterns for games).
      if (type === 'game' && i < 2) {
        result = result.replaceAll(regex, `[${shortcode}=$1?set=$2]`);
      } else {
        result = result.replaceAll(regex, `[${shortcode}=$1]`);
      }
    }
  }

  // Clean up nested shortcodes in url tags.
  // "[url=[user=Scott]]" -> "[user=Scott]"
  result = result.replace(/\[url=\[(\w+=[^\]]+)\]\]/g, '[$1]');

  return result;
}
