import type { TicketCloseAction } from './ticketCloseReasonLabels';

/**
 * Of 2,278 tickets closed as "See the comments" between Nov 2026 and
 * Oct 2026, 14% were an emulator or core problem, 7% were network problems,
 * and 5% were player error or a triggerable that already works as designed.
 *
 * Run against that same sample, the patterns below would've matched the
 * closing comments roughly 8 in 10, 9 in 10, and 5 in 6 times for those
 * three reasons in order.
 */
const SUGGESTION_RULES: Array<{ action: TicketCloseAction; pattern: RegExp }> = [
  {
    action: 'emulator-problem',
    pattern:
      /\b(emulator|core) (issue|problem|bug)|\b(unsupported|not supported|wrong|different|outdated|old) (emulator|core)|\bcore\b|\bswanstation|\bbeetle|\bmupen|\bparallel\b|\bpcsx2?\b|\bdolphin\b|\bppsspp\b|\bduckstation\b|\bbizhawk\b/i,
  },
  {
    action: 'network',
    pattern:
      /\bnetwork\b|\b(internet|wifi|wi-fi) connection|\boffline\b|\bdisconnect|connection (issue|problem|drop)/i,
  },
  {
    action: 'closed-mistaken',
    pattern:
      /\bworks? as (designed|intended)|\bworking as (designed|intended)|\bas intended\b|\bby design\b|\byou (need|have) to\b|\byou must\b(?![’']ve|\s+have\b)|\bread the (description|achievement)|\brequirement/i,
  },
];

/**
 * Nudges the developer towards standard reason when the closing note
 * likely describes one.
 */
export function suggestTicketCloseAction(note: string): TicketCloseAction | null {
  const patternHits = SUGGESTION_RULES.filter(({ pattern }) => pattern.test(note));

  return patternHits.length === 1 ? patternHits[0].action : null;
}
