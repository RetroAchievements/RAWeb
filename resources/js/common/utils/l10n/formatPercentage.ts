/**
 * Formats a number as a percentage. Optionally omits the percentage sign.
 *
 * 👉 This is a low-level utility. It requires you to manually pass in the
 *    user's locale in order to display correctly-formatted percentages.
 *    Consider `useFormatPercentage()` instead. It automates that away.
 *
 * @returns A localized percentage. eg: 0.1234 -> "12.34%"
 */
export function formatPercentage(
  number: number,
  formatterOptions?: Partial<{
    minimumFractionDigits: number;
    maximumFractionDigits: number;
    locale: string;
    omitSign: boolean;
  }>,
): string {
  const formatter = new Intl.NumberFormat(formatterOptions?.locale?.replace('_', '-') ?? 'en-US', {
    style: 'percent',
    minimumFractionDigits: formatterOptions?.minimumFractionDigits ?? 2,
    maximumFractionDigits: formatterOptions?.maximumFractionDigits ?? 2,
  });

  const formattedValue = formatter.format(number);

  return formatterOptions?.omitSign ? formattedValue.replace('%', '') : formattedValue;
}
