import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import { BaseForm } from '@/common/components/+vendor/BaseForm';
import { cn } from '@/common/utils/cn';

import { SetToggle } from './SetToggle';
import { useSubsetConfigurationForm } from './useSubsetConfigurationForm';

interface SubsetConfigurationFormProps {
  configurableSets: App.Platform.Data.GameAchievementSet[];
  onSubmitSuccess: () => void;
}

export const SubsetConfigurationForm: FC<SubsetConfigurationFormProps> = ({
  configurableSets,
  onSubmitSuccess,
}) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useSubsetConfigurationForm({
    configurableSets,
    onSubmitSuccess,
  });

  const noSetupSets = configurableSets.filter(
    (set) => set.type === 'core' || set.type === 'bonus' || set.type === 'challenge',
  );
  const patchRequiredSets = configurableSets.filter((set) => set.type === 'specialty');
  const hasBothTypes = noSetupSets.length > 0 && patchRequiredSets.length > 0;

  return (
    <BaseForm {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} name="subset-configuration">
        <div
          className={cn(
            'flex max-h-[30vh] flex-col gap-4 overflow-auto rounded-md bg-neutral-950 p-3 sm:max-h-[35vh] sm:p-4',
            'light:border light:border-neutral-200 light:bg-white',
          )}
        >
          {hasBothTypes ? (
            <div className="flex flex-col gap-5">
              <div className="flex flex-col gap-3">
                <p className="text-xs font-bold uppercase tracking-wide text-neutral-300 light:text-neutral-600">
                  {t('No extra setup needed')}
                </p>

                {noSetupSets.map((set) => (
                  <SetToggle key={set.id} configurableSet={set} control={form.control} />
                ))}
              </div>

              <div className="flex flex-col gap-3">
                <p className="text-xs font-bold uppercase tracking-wide text-neutral-300 light:text-neutral-600">
                  {t('Requires a patched game file')}
                </p>

                {patchRequiredSets.map((set) => (
                  <SetToggle key={set.id} configurableSet={set} control={form.control} />
                ))}
              </div>
            </div>
          ) : (
            configurableSets.map((set) => (
              <SetToggle key={set.id} configurableSet={set} control={form.control} />
            ))
          )}
        </div>

        <BaseDialogFooter className="pt-8">
          <BaseButton type="submit" disabled={!form.formState.isDirty || mutation.isPending}>
            {t('Save')}
          </BaseButton>
        </BaseDialogFooter>
      </form>
    </BaseForm>
  );
};
