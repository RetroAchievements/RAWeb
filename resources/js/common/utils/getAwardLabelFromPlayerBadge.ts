import { AwardType } from './generatedAppConstants';

export function getAwardLabelFromPlayerBadge(playerBadge: App.Platform.Data.PlayerBadge): string {
  let awardLabel = 'Finished';

  const { awardType, awardDataExtra } = playerBadge;

  if (awardType === AwardType.Mastery) {
    awardLabel = awardDataExtra ? 'Mastered' : 'Completed';
  } else if (awardType === AwardType.GameBeaten) {
    awardLabel = awardDataExtra ? 'Beaten' : 'Beaten (softcore)';
  }

  return awardLabel;
}
