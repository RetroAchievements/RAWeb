import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import { cn } from '@/common/utils/cn';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

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

  return (
    <BaseForm {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} name="subset-configuration">
        <div
          className={cn(
            'flex max-h-[30vh] flex-col gap-4 overflow-auto rounded-md bg-neutral-950 p-3 sm:max-h-[35vh] sm:p-4',
            'light:border light:border-neutral-200 light:bg-white',
          )}
        >
          {configurableSets.map((configurableSet) => (
            <BaseFormField
              key={`configurable-set-${configurableSet.id}`}
              control={form.control}
              name={`preferences.${configurableSet.id}`}
              render={({ field }) => (
                <BaseFormItem className="flex w-full items-center justify-between">
                  <div className="flex items-center gap-2">
                    <img
                      src={configurableSet.achievementSet.imageAssetPathUrl}
                      alt={configurableSet.title as string}
                      className="size-8 rounded-sm"
                    />

                    <BaseFormLabel>{configurableSet.title ?? BASE_SET_LABEL}</BaseFormLabel>
                  </div>

                  <div className="flex items-center gap-2">
                    <span
                      className={cn(
                        'hidden text-xs sm:block',
                        field.value ? 'text-text' : 'text-neutral-700',
                      )}
                    >
                      {field.value ? t('Opted In') : t('Opted Out')}
                    </span>

                    <BaseFormControl>
                      <BaseSwitch
                        checked={field.value}
                        onCheckedChange={(newValue) => {
                          field.onChange(newValue);
                        }}
                      />
                    </BaseFormControl>
                  </div>
                </BaseFormItem>
              )}
            />
          ))}
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
