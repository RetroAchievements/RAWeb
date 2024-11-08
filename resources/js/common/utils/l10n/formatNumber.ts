/**
 * Number.prototype.toLocaleString() can be quite slow if called frequently.
 * Using Intl.NumberFormat with a given locale is generally much faster.
 *
 * 👉 This is a low-level utility. It requires you to manually pass in the
 *    user's locale in order to display correctly-formatted numbers.
 *    Consider `useFormatNumber()` instead. It automates that away.
 *
 * @returns A localized number. eg: 12345 -> "12,345"
 */
export function formatNumber(
  number: number,
  formatterOptions?: Partial<{ locale: string }>,
): string {
  const formatter = new Intl.NumberFormat(formatterOptions?.locale?.replace('_', '-') ?? 'en-US');

  return formatter.format(number);
}
