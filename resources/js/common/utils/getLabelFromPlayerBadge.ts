import { AwardType } from './generatedAppConstants';

export function getLabelFromPlayerBadge(awardType: number, awardDataExtra: number): string {
  let awardLabel = 'Finished';

  if (awardType === AwardType.Mastery) {
    awardLabel = awardDataExtra ? 'Mastered' : 'Completed';
  } else if (awardType === AwardType.GameBeaten) {
    awardLabel = awardDataExtra ? 'Beaten' : 'Beaten (softcore)';
  }

  return awardLabel;
}
