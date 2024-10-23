import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AwardType } from '../utils/generatedAppConstants';

export function useGetAwardLabelFromPlayerBadge() {
  const { t } = useLaravelReactI18n();

  const getAwardLabelFromPlayerBadge = (playerBadge: App.Platform.Data.PlayerBadge): string => {
    let awardLabel = t('Finished');

    const { awardType, awardDataExtra } = playerBadge;

    if (awardType === AwardType.Mastery) {
      awardLabel = awardDataExtra ? t('Mastered') : t('Completed');
    } else if (awardType === AwardType.GameBeaten) {
      awardLabel = awardDataExtra ? t('Beaten') : t('Beaten (softcore)');
    }

    return awardLabel;
  };

  return { getAwardLabelFromPlayerBadge };
}
