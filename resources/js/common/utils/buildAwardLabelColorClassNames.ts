import { AwardType } from '@/common/utils/generatedAppConstants';

/**
 * Generates CSS class names for game award labels based on the award type and
 * whether the award is for hardcore mode (awardDataExtra 0 or 1).
 *
 * Acceptable awardType values are AwardType.Mastery and AwardType.GameBeaten.
 * Unknown awards will fall back to null.
 */
export function buildAwardLabelColorClassNames(
  awardType?: number,
  awardDataExtra?: number,
  variant: 'base' | 'muted-group' = 'base',
): string | null {
  if (awardType === undefined || awardDataExtra === undefined) {
    return null;
  }

  const baseColors: Record<number, string> = {
    [AwardType.Mastery]: awardDataExtra
      ? 'text-[gold] light:text-yellow-600' // Mastery
      : 'text-yellow-600', // Completion
    [AwardType.GameBeaten]: awardDataExtra
      ? 'text-zinc-300' // Beaten
      : 'text-zinc-400', // Beaten (softcore)
  };

  const mutedGroupColors: Record<number, string> = {
    [AwardType.Mastery]: awardDataExtra
      ? 'transition text-muted group-hover:text-[gold] group-hover:light:text-yellow-600' // Mastery
      : 'transition text-muted group-hover:text-yellow-600', // Completion

    [AwardType.GameBeaten]: awardDataExtra
      ? 'transition text-muted group-hover:text-zinc-300' // Beaten
      : 'transition text-muted group-hover:text-zinc-400', // Beaten (softcore)
  };

  if (variant === 'muted-group') {
    return mutedGroupColors[awardType] ?? null;
  }

  return baseColors[awardType] ?? null;
}
