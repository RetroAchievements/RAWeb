import { getPluralIndex } from '../contrib/get-plural-index';

/**
 * Select a proper translation string based on the given number.
 *
 * @param message
 * @param number
 * @param locale
 */
export default function pluralization(message: string, number: number, locale: string): string {
  let segments = message.split('|');
  const extracted = extract(segments, number);

  if (extracted !== null) {
    return extracted.trim();
  }

  segments = stripConditions(segments);
  const pluralIndex = getPluralIndex(locale, number);

  if (segments.length === 1 || !segments[pluralIndex]) {
    return segments[0];
  }

  return segments[pluralIndex];
}

/**
 * Extract a translation string using inline conditions.
 *
 * @param segments
 * @param number
 */
function extract(segments: string[], number: number): string | null {
  let result: string | null = null;

  for (const segment of segments) {
    if (result !== null) continue;
    result = extractFromString(segment, number);
  }

  return result;
}

/**
 * Get the translation string if the condition matches.
 *
 * @param part
 * @param number
 */
function extractFromString(part: string, number: number): string | null {
  const matches = part.match(/^[{[]([^[\]{}]*)[}\]](.*)/s) || [];

  if (!matches?.[1] && !matches?.[2]) {
    return null;
  }

  const condition = matches[1];
  const value = matches[2];

  if (condition.includes(',')) {
    const [from, to] = condition.split(',');

    if (
      (to === '*' && number >= parseFloat(from)) ||
      (from === '*' && number <= parseFloat(to)) ||
      (number >= parseFloat(from) && number <= parseFloat(to))
    ) {
      return value;
    }
  }

  return parseFloat(condition) === number ? value : null;
}

/**
 * Strip the inline conditions from each segment, just leaving the text.
 *
 * @param segments
 */
function stripConditions(segments: string[]): string[] {
  return segments.map((part) => part.replace(/^[{[]([^[\]{}]*)[}\]]/, ''));
}
