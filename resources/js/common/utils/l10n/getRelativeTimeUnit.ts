import type { RelativeTimeUnit } from '@/common/models';

type CountedUnit = Exclude<RelativeTimeUnit, 'second'>;

/**
 * @param maxUnit Caps the unit, so `'day'` turns "2 weeks" into "14 days".
 */
export function getRelativeTimeUnit(
  seconds: number,
  maxUnit?: 'day' | 'week' | 'month',
): { unit: CountedUnit; value: number } {
  let unit: CountedUnit;
  let divisor: number;

  if (seconds < 3600) {
    unit = 'minute';
    divisor = 60;
  } else if (seconds < 86_400) {
    unit = 'hour';
    divisor = 3600;
  } else if (seconds < 604_800) {
    unit = 'day';
    divisor = 86_400;
  } else if (seconds < 2_629_743) {
    unit = 'week';
    divisor = 604_800;
  } else if (seconds < 31_556_926) {
    unit = 'month';
    divisor = 2_629_743;
  } else {
    unit = 'year';
    divisor = 31_556_926;
  }

  if (maxUnit === 'day' && ['week', 'month', 'year'].includes(unit)) {
    unit = 'day';
    divisor = 86_400;
  } else if (maxUnit === 'week' && ['month', 'year'].includes(unit)) {
    unit = 'week';
    divisor = 604_800;
  } else if (maxUnit === 'month' && unit === 'year') {
    unit = 'month';
    divisor = 2_629_743;
  }

  return { unit, value: Math.floor(seconds / divisor) };
}
