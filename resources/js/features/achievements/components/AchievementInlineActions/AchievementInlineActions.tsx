import { useAtomValue, useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuCode, LuPencil, LuWrench, LuX } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAchievementQuickEditActions } from '../../hooks/useAchievementQuickEditActions';
import {
  isEditModeAtom,
  isResetProgressDialogOpenAtom,
  isUpdatePromotedStatusDialogOpenAtom,
} from '../../state/achievements.atoms';

export const AchievementInlineActions: FC = () => {
  const { achievement, can, eventAchievement, isEventGame } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const setIsUpdatePromotedStatusDialogOpen = useSetAtom(isUpdatePromotedStatusDialogOpenAtom);
  const setIsResetProgressDialogOpen = useSetAtom(isResetProgressDialogOpenAtom);
  const isEditMode = useAtomValue(isEditModeAtom);
  const setIsEditMode = useSetAtom(isEditModeAtom);
  const { handleSave, handleCancel, isSaving } = useAchievementQuickEditActions();

  const hasUnlocked = achievement.unlockedAt || achievement.unlockedHardcoreAt;

  // For event achievements, tickets and reports target the source achievement.
  const sourceAchievement = eventAchievement?.sourceAchievement;
  const reportSubject = sourceAchievement ?? achievement;
  const ticketCount = sourceAchievement?.numUnresolvedTickets ?? achievement.numUnresolvedTickets;

  return (
    <div className="flex flex-col gap-2 text-xs md:flex-row md:items-center md:justify-between">
      {!isEventGame || sourceAchievement ? (
        <div className="flex divide-x divide-neutral-700">
          <InertiaLink
            href={route('achievement.report-issue', { achievement: reportSubject.id })}
            prefetch="desktop-hover-only"
          >
            <span className="pr-3">{t('Report an issue')}</span>
          </InertiaLink>

          {ticketCount ? (
            <a
              href={route('achievement.tickets', { achievement: reportSubject.id })}
              className="px-3"
            >
              {t('openTicketCount', {
                count: ticketCount,
                val: ticketCount,
              })}
            </a>
          ) : (
            <p className="px-3 italic text-neutral-600">{t('No open tickets')}</p>
          )}
        </div>
      ) : null}

      <div className="flex items-center gap-3">
        {can?.develop && !isEditMode ? (
          <div className="flex items-center gap-2 lg:hidden">
            <a
              href={`/manage/achievements/${achievement.id}`}
              target="_blank"
              className="flex items-center gap-1"
            >
              <LuWrench className="size-3" />
              {t('Manage')}
            </a>

            {!isEventGame && can.viewAchievementLogic ? (
              <a
                href={`/manage/achievements/${achievement.id}/logic`}
                target="_blank"
                className="flex items-center gap-1"
              >
                <LuCode className="size-3" />
                {t('Logic')}
              </a>
            ) : null}

            {!isEventGame ? (
              <BaseButton onClick={() => setIsEditMode(true)} size="xs" className="gap-1">
                <LuPencil className="size-3" />
                {t('Quick edit')}
              </BaseButton>
            ) : null}
          </div>
        ) : null}

        {isEditMode && can?.develop ? (
          <div className="flex items-center gap-3">
            <BaseButton onClick={handleCancel} size="xs" className="gap-1">
              <LuX className="size-3" />
              {t('Cancel')}
            </BaseButton>

            <BaseButton onClick={handleSave} size="xs" className="gap-1" disabled={isSaving}>
              <LuCheck className="size-3" />
              {t('Save')}
            </BaseButton>
          </div>
        ) : null}

        {isEditMode && can?.updateAchievementIsPromoted ? (
          <BaseButton
            onClick={() => setIsUpdatePromotedStatusDialogOpen(true)}
            variant={achievement.isPromoted ? 'destructive' : 'default'}
            size="xs"
          >
            {achievement.isPromoted ? t('Demote') : t('Promote')}
          </BaseButton>
        ) : null}

        {hasUnlocked && !isEventGame ? (
          <BaseButton
            onClick={() => setIsResetProgressDialogOpen(true)}
            variant="destructive"
            size="xs"
          >
            {t('Reset progress')}
          </BaseButton>
        ) : null}
      </div>
    </div>
  );
};
