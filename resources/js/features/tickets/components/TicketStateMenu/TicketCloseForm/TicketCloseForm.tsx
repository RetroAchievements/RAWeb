import type { FC } from 'react';
import { useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialogClose, BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import { BaseForm } from '@/common/components/+vendor/BaseForm';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketCloseAnswer } from '../../../models';
import { getAllowedTicketCloseAnswerGroups } from '../../../utils/getAllowedTicketCloseAnswerGroups';
import { TicketCloseNoteField } from './TicketCloseNoteField';
import { TicketCloseQuestion } from './TicketCloseQuestion';
import { TicketCloseReasonQuestion } from './TicketCloseReasonQuestion';
import { TicketCoveringTicketPicker } from './TicketCoveringTicketPicker';
import { useTicketCloseForm } from './useTicketCloseForm';

interface TicketCloseFormProps {
  onSubmitSuccess: () => void;
}

export const TicketCloseForm: FC<TicketCloseFormProps> = ({ onSubmitSuccess }) => {
  const { achievement, allowedTicketActions, can, leaderboard, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useTicketCloseForm({ onSubmitSuccess });

  const [answer, action] = useWatch({ control: form.control, name: ['answer', 'action'] });

  const canAttachComment = !!can.createTicketComments;

  const isTicketablePromoted = achievement
    ? !!achievement.isPromoted
    : leaderboard?.state !== 'unpromoted';

  const actionsThatRequireComment: App.Community.Enums.TicketAction[] = [
    'closed-other',
    'covered-by-another-ticket',
  ];
  const closeAnswerGroups = getAllowedTicketCloseAnswerGroups(
    canAttachComment
      ? allowedTicketActions
      : allowedTicketActions.filter(
          (closeAction) => !actionsThatRequireComment.includes(closeAction),
        ),
  );
  const selectedAnswerGroup = closeAnswerGroups.find((group) => group.answer === answer);

  const labelByAnswer: Record<TicketCloseAnswer, TranslatedString> = {
    yes: t('Yes'),
    no: t('No'),
    'cant-tell': t("Can't tell"),
    covered: t('Already covered by another ticket'),
  };

  const handleAnswerSelect = (value: string) => {
    const pickedAnswerGroup = closeAnswerGroups.find((group) => group.answer === value);

    const preselectedAction =
      pickedAnswerGroup?.answer === 'covered' ? pickedAnswerGroup.actions[0] : null;

    form.setValue('answer', pickedAnswerGroup?.answer ?? null);
    form.setValue('coveringTicketId', null);
    form.setValue('action', preselectedAction, { shouldValidate: true });
  };

  const handleReasonSelect = (value: string) => {
    const chosenCloseAction =
      selectedAnswerGroup?.actions.find((closeAction) => closeAction === value) ?? null;

    form.setValue('action', chosenCloseAction, { shouldValidate: true });
  };

  const getSubmitLabel = () => {
    if (action === 'resolved') {
      return t('Resolve ticket');
    }

    if (action === 'demoted' && isTicketablePromoted) {
      return ticket.ticketableType === 'achievement'
        ? t('Demote and close')
        : t('Unpromote and close');
    }

    return t('Close ticket');
  };

  return (
    <BaseForm {...form}>
      <form
        name="close-ticket"
        onSubmit={(event) => {
          // React bubbles the submit up to the comment form, which then would otherwise
          // throw a validation error. Stop propagation to prevent that from happening.
          event.stopPropagation();

          return form.handleSubmit(onSubmit)(event);
        }}
      >
        <div className="flex flex-col gap-5">
          <div className="grid gap-x-8 gap-y-5 sm:grid-cols-2">
            <TicketCloseQuestion
              label={
                ticket.ticketableType === 'achievement'
                  ? t('Was something wrong with the achievement?')
                  : t('Was something wrong with the leaderboard?')
              }
              options={closeAnswerGroups.map((group) => ({
                label: labelByAnswer[group.answer],
                value: group.answer,
              }))}
              value={answer}
              onValueChange={handleAnswerSelect}
            />

            <div className={cn('grid *:col-start-1 *:row-start-1', !answer && 'max-sm:hidden')}>
              <p
                aria-hidden={answer !== null}
                className={cn(
                  'text-sm text-neutral-400 max-sm:hidden light:text-neutral-600',
                  answer !== null && 'invisible',
                )}
              >
                {t('Pick an answer first.')}
              </p>

              {closeAnswerGroups.map((group) => {
                const isGroupVisible = group.answer === answer;

                return (
                  <div
                    key={group.answer}
                    aria-hidden={!isGroupVisible}
                    inert={!isGroupVisible}
                    className={cn(!isGroupVisible && 'invisible')}
                  >
                    {group.answer === 'covered' ? (
                      <TicketCoveringTicketPicker />
                    ) : (
                      <TicketCloseReasonQuestion
                        actions={group.actions}
                        isTicketablePromoted={isTicketablePromoted}
                        value={isGroupVisible ? action : null}
                        onValueChange={handleReasonSelect}
                      />
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {canAttachComment ? <TicketCloseNoteField closeAnswerGroups={closeAnswerGroups} /> : null}
        </div>

        <BaseDialogFooter className="mt-4">
          <BaseDialogClose asChild>
            <BaseButton type="button" variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            type="submit"
            size="sm"
            disabled={!form.formState.isValid || mutation.isPending}
          >
            {getSubmitLabel()}
          </BaseButton>
        </BaseDialogFooter>
      </form>
    </BaseForm>
  );
};
