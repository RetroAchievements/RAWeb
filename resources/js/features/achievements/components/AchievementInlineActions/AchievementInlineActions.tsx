import { useAtomValue, useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuCode, LuEllipsis, LuPencil, LuWrench, LuX } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuItem,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
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

  const canResetProgress = hasUnlocked && !isEventGame;

  return (
    <div className="flex items-center justify-between gap-2 text-xs">
      {(!isEventGame || sourceAchievement) && can?.createTicket ? (
        <div className="flex items-center gap-3">
          <InertiaLink
            href={route('achievement.report-issue', { achievement: reportSubject.id })}
            prefetch="desktop-hover-only"
          >
            {t('Report an issue')}
          </InertiaLink>

          {ticketCount ? (
            <>
              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>

              <a href={route('achievement.tickets', { achievement: reportSubject.id })}>
                {t('openTicketCount', {
                  count: ticketCount,
                  val: ticketCount,
                })}
              </a>
            </>
          ) : null}
        </div>
      ) : null}

      <div className="flex items-center gap-1.5">
        {isEditMode && can?.quickEditAchievement ? (
          <div className="flex items-center gap-1.5">
            <BaseButton onClick={handleCancel} size="xs" className="gap-1">
              <LuX className="size-3" />
              {t('Cancel')}
            </BaseButton>

            <BaseButton onClick={handleSave} size="xs" className="gap-1" disabled={isSaving}>
              <LuCheck className="size-3" />
              {t('Save')}
            </BaseButton>

            {can.updateAchievementIsPromoted ? (
              <BaseButton
                onClick={() => setIsUpdatePromotedStatusDialogOpen(true)}
                variant={achievement.isPromoted ? 'destructive' : 'default'}
                size="xs"
              >
                {achievement.isPromoted ? t('Demote') : t('Promote')}
              </BaseButton>
            ) : null}
          </div>
        ) : null}

        {!isEditMode ? (
          <>
            {/* Desktop: show Reset progress as a direct button */}
            {canResetProgress ? (
              <BaseButton
                onClick={() => setIsResetProgressDialogOpen(true)}
                variant="destructive"
                size="xs"
                className="hidden lg:inline-flex"
              >
                {t('Reset progress')}
              </BaseButton>
            ) : null}

            {/* Mobile: show an overflow menu with dev tools and reset progress */}
            {can?.manageAchievements ||
            can?.quickEditAchievement ||
            can?.viewAchievementLogic ||
            canResetProgress ? (
              <div className="lg:hidden">
                <BaseDropdownMenu>
                  <BaseDropdownMenuTrigger asChild>
                    <BaseButton size="xs" aria-label={t('More actions')}>
                      <LuEllipsis className="size-4" />
                    </BaseButton>
                  </BaseDropdownMenuTrigger>

                  <BaseDropdownMenuContent align="end">
                    {/* Manage button: visible if can.develop */}
                    {can?.manageAchievements ? (
                      <BaseDropdownMenuItem asChild className="cursor-pointer gap-2">
                        <a href={`/manage/achievements/${achievement.id}`} target="_blank">
                          <LuWrench className="size-3.5" />
                          {t('Manage')}
                        </a>
                      </BaseDropdownMenuItem>
                    ) : null}

                    {/* Logic button: visible if not an event game and can.viewAchievementLogic */}
                    {!isEventGame && can?.viewAchievementLogic ? (
                      <BaseDropdownMenuItem asChild className="cursor-pointer gap-2">
                        <a href={`/manage/achievements/${achievement.id}/logic`} target="_blank">
                          <LuCode className="size-3.5" />
                          {t('Logic')}
                        </a>
                      </BaseDropdownMenuItem>
                    ) : null}

                    {/* Quick Edit button: visible if can.quickEditAchievement and not an event game */}
                    {can?.quickEditAchievement && !isEventGame ? (
                      <BaseDropdownMenuItem
                        className="cursor-pointer gap-2 text-link"
                        onClick={() => setIsEditMode(true)}
                      >
                        <LuPencil className="size-3.5" />
                        {t('Quick edit')}
                      </BaseDropdownMenuItem>
                    ) : null}

                    {canResetProgress ? (
                      <>
                        {can?.manageAchievements ||
                        can?.quickEditAchievement ||
                        can?.viewAchievementLogic ? (
                          <BaseDropdownMenuSeparator />
                        ) : null}

                        <BaseDropdownMenuItem
                          className="cursor-pointer gap-2 text-red-400 focus:text-red-300"
                          onClick={() => setIsResetProgressDialogOpen(true)}
                        >
                          {t('Reset progress')}
                        </BaseDropdownMenuItem>
                      </>
                    ) : null}
                  </BaseDropdownMenuContent>
                </BaseDropdownMenu>
              </div>
            ) : null}
          </>
        ) : null}
      </div>
    </div>
  );
};
