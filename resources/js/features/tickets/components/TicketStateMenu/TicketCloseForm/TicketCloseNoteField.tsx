import type { FC } from 'react';
import { useFormContext, useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseTextarea } from '@/common/components/+vendor/BaseTextarea';

import type { TicketCloseAnswerGroup } from '../../../models';
import { suggestTicketCloseAction } from '../../../utils/suggestTicketCloseAction';
import { TICKET_CLOSE_REASON_LABELS } from '../../../utils/ticketCloseReasonLabels';
import type { FormValues } from './useTicketCloseForm';

interface TicketCloseNoteFieldProps {
  closeAnswerGroups: TicketCloseAnswerGroup[];
}

export const TicketCloseNoteField: FC<TicketCloseNoteFieldProps> = ({ closeAnswerGroups }) => {
  const { t } = useTranslation();
  const { control, setValue } = useFormContext<FormValues>();

  const [action, noteText] = useWatch({ control, name: ['action', 'note'] });

  const isNoteMandatory = action === 'closed-other';
  const isNoteAbsent = isNoteMandatory && !noteText.trim();

  // When a "Something else" note describes one of the standard reasons, offer that reason.
  const suggestedCloseAction = isNoteMandatory ? suggestTicketCloseAction(noteText) : null;
  const suggestionAnswerGroup = closeAnswerGroups.find(
    (group) => suggestedCloseAction !== null && group.actions.includes(suggestedCloseAction),
  );
  const closeSuggestion =
    suggestedCloseAction && suggestionAnswerGroup
      ? { action: suggestedCloseAction, answer: suggestionAnswerGroup.answer }
      : null;

  return (
    <BaseFormField
      control={control}
      name="note"
      render={({ field, fieldState }) => (
        <BaseFormItem className="flex flex-col gap-2">
          <BaseFormLabel>
            {isNoteMandatory
              ? t('Note for the reporter (required)')
              : t('Note for the reporter (optional)')}
          </BaseFormLabel>

          <BaseFormControl>
            <BaseTextarea {...field} rows={5} disabled={!action} />
          </BaseFormControl>

          <div className="min-h-lh text-sm">
            {isNoteAbsent ? (
              <p className="text-neutral-400 light:text-neutral-600">
                {t('A note is required to close this ticket.')}
              </p>
            ) : null}

            {!isNoteAbsent && fieldState.error ? <BaseFormMessage /> : null}

            {!isNoteAbsent && !fieldState.error && closeSuggestion ? (
              <p className="text-neutral-400 light:text-neutral-600">
                {t('{{reason}}?', {
                  reason: t(TICKET_CLOSE_REASON_LABELS[closeSuggestion.action]),
                })}{' '}
                <BaseButton
                  type="button"
                  variant="link"
                  className="h-auto p-0 align-baseline"
                  onClick={() => {
                    setValue('answer', closeSuggestion.answer);
                    setValue('action', closeSuggestion.action, { shouldValidate: true });
                  }}
                >
                  {t('Pick this')}
                </BaseButton>
              </p>
            ) : null}
          </div>
        </BaseFormItem>
      )}
    />
  );
};
