/**
 * Number.prototype.toLocaleString() can be quite slow if called frequently.
 * User Intl.NumberFormat with a given locale is generally much faster.
 *
 * TODO pass in the user's locale from the `auth` object in page props
 *
 * @returns A localized number. eg: 12345 -> "12,345"
 */
export function formatNumber(
  number: number,
  formatterOptions?: Partial<{ locale: string }>,
): string {
  const formatter = new Intl.NumberFormat(formatterOptions?.locale ?? 'en-US');

  return formatter.format(number);
}
