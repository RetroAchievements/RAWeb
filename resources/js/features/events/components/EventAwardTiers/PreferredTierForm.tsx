import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import { cn } from '@/common/utils/cn';

import { cleanEventAwardLabel } from '../../utils/cleanEventAwardLabel';
import { usePreferredTierForm } from './usePreferredTierForm';

interface PreferredTierFormProps {
  earnedTierIndex: number;
  event: App.Platform.Data.Event;
  eventAwards: App.Platform.Data.EventAward[];
  initialTierIndex: number;
  onSubmitSuccess: () => void;
}

export const PreferredTierForm: FC<PreferredTierFormProps> = ({
  earnedTierIndex,
  event,
  eventAwards,
  initialTierIndex,
  onSubmitSuccess,
}) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = usePreferredTierForm({
    eventId: event.id,
    initialTierIndex,
    onSubmitSuccess,
  });

  const selectedTierIndex = form.watch('tierIndex');

  // Only show tiers the user has earned (tierIndex <= earned tier).
  const earnedAwards = eventAwards
    .filter((award) => award.tierIndex <= earnedTierIndex)
    .sort((a, b) => b.tierIndex - a.tierIndex);

  return (
    <form onSubmit={form.handleSubmit(onSubmit)} name="preferred-tier">
      <div
        role="radiogroup"
        aria-label={t('Award tier selection')}
        className={cn(
          'flex flex-col gap-2 rounded-md bg-neutral-950 p-3 sm:p-4',
          'light:border light:border-neutral-200 light:bg-white',
        )}
      >
        {earnedAwards.map((award) => {
          const isSelected = selectedTierIndex === award.tierIndex;
          const cleanedLabel = cleanEventAwardLabel(award.label, event);

          return (
            <button
              key={award.tierIndex}
              type="button"
              role="radio"
              aria-checked={isSelected}
              className={cn(
                'flex items-center gap-3 rounded-lg p-2 transition',
                isSelected
                  ? 'bg-zinc-700/50 ring-1 ring-neutral-500 light:bg-neutral-100 light:ring-neutral-400'
                  : 'bg-zinc-800/50 hover:bg-zinc-700/30 light:bg-neutral-50 light:hover:bg-neutral-100',
              )}
              onClick={() => form.setValue('tierIndex', award.tierIndex, { shouldDirty: true })}
            >
              <img src={award.badgeUrl} alt={award.label} className="size-10 rounded-sm" />

              <span className="flex-1 text-left text-sm font-medium">{cleanedLabel}</span>

              {isSelected ? (
                <div
                  aria-hidden="true"
                  className={cn(
                    'flex size-6 items-center justify-center rounded-full',
                    'bg-embed light:bg-neutral-200 light:text-neutral-700',
                  )}
                >
                  <LuCheck className="size-4" />
                </div>
              ) : null}
            </button>
          );
        })}
      </div>

      <BaseDialogFooter className="pt-8">
        <BaseButton type="submit" disabled={!form.formState.isDirty || mutation.isPending}>
          {t('Save')}
        </BaseButton>
      </BaseDialogFooter>
    </form>
  );
};
