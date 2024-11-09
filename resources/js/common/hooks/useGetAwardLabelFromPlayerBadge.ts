import { useTranslation } from 'react-i18next';

import { AwardType } from '../utils/generatedAppConstants';

export function useGetAwardLabelFromPlayerBadge() {
  const { t } = useTranslation();

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
