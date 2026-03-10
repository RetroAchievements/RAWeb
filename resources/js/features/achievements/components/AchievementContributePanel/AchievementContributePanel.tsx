import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCode, LuPencil, LuWrench, LuX } from 'react-icons/lu';

import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAchievementQuickEditActions } from '../../hooks/useAchievementQuickEditActions';
import { isEditModeAtom } from '../../state/achievements.atoms';

export const AchievementContributePanel: FC = () => {
  const { achievement, can } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const [isEditMode, setIsEditMode] = useAtom(isEditModeAtom);
  const { handleCancel } = useAchievementQuickEditActions();

  if (!can?.develop) {
    return null;
  }

  return (
    <div className="hidden lg:flex lg:flex-col lg:gap-6">
      <PlayableSidebarButtonsSection headingLabel={t('Contribute')}>
        {isEditMode ? (
          <PlayableSidebarButton onClick={handleCancel} IconComponent={LuX}>
            {t('Cancel Editing')}
          </PlayableSidebarButton>
        ) : (
          <PlayableSidebarButton onClick={() => setIsEditMode(true)} IconComponent={LuPencil}>
            {t('Quick Edit')}
          </PlayableSidebarButton>
        )}

        <div className="grid grid-cols-2 gap-1">
          <PlayableSidebarButton
            href={`/manage/achievements/${achievement.id}`}
            target="_blank"
            IconComponent={LuWrench}
          >
            {t('Manage')}
          </PlayableSidebarButton>

          {can.viewAchievementLogic ? (
            <PlayableSidebarButton
              href={`/manage/achievements/${achievement.id}/logic`}
              target="_blank"
              IconComponent={LuCode}
            >
              {t('Logic')}
            </PlayableSidebarButton>
          ) : null}
        </div>
      </PlayableSidebarButtonsSection>

      <BaseSeparator />
    </div>
  );
};
