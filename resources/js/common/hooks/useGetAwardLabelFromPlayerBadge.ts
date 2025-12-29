import { useTranslation } from 'react-i18next';

export function useGetAwardLabelFromPlayerBadge() {
  const { t } = useTranslation();

  const getAwardLabelFromPlayerBadge = (playerBadge: App.Platform.Data.PlayerBadge): string => {
    let awardLabel = t('Finished');

    const { awardType, awardTier } = playerBadge;

    if (awardType === 'mastery') {
      awardLabel = awardTier ? t('Mastered') : t('Completed');
    } else if (awardType === 'game_beaten') {
      awardLabel = awardTier ? t('Beaten') : t('Beaten (softcore)');
    }

    return awardLabel;
  };

  return { getAwardLabelFromPlayerBadge };
}
