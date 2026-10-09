import type { RelativeTimeUnit } from '@/common/models';

import { getRelativeTimeUnit } from './getRelativeTimeUnit';

/**
 * Produces a rough, human length of time for sentences like "2 weeks after the report".
 *
 * @example formatCoarseDuration(5520, 'en_US') -> "1 hour 32 minutes"
 * @example formatCoarseDuration(19 * 86400, 'en_US') -> "2 weeks"
 */
export function formatCoarseDuration(seconds: number, locale: string): string {
  const browserLocale = locale.replace('_', '-');

  const renderUnit = (unit: Exclude<RelativeTimeUnit, 'second'>, value: number) =>
    new Intl.NumberFormat(browserLocale, { style: 'unit', unit, unitDisplay: 'long' }).format(
      value,
    );

  const { unit, value } = getRelativeTimeUnit(seconds);

  if (unit === 'minute') {
    return renderUnit('minute', Math.max(1, value));
  }

  if (unit === 'hour') {
    const hourCount = value;
    const minuteCount = Math.floor(seconds / 60) % 60;

    const chunks = [renderUnit('hour', hourCount)];
    if (minuteCount > 0) {
      chunks.push(renderUnit('minute', minuteCount));
    }

    return new Intl.ListFormat(browserLocale, { type: 'unit', style: 'narrow' }).format(chunks);
  }

  return renderUnit(unit, value);
}
