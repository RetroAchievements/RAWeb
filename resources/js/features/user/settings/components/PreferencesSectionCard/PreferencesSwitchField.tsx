import { type FC } from 'react';
import type { Control } from 'react-hook-form';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import type { FormValues as PreferencesSectionFormValues } from './usePreferencesSectionForm';

type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];

interface PreferencesTableRowProps {
  label: string;
  fieldName: UserPreferenceValue;
  control: Control<PreferencesSectionFormValues>;
}

export const PreferencesSwitchField: FC<PreferencesTableRowProps> = ({
  label,
  fieldName,
  control,
}) => {
  return (
    <BaseFormField
      control={control}
      name={fieldName as keyof PreferencesSectionFormValues}
      render={({ field }) => (
        <BaseFormItem className="flex w-full items-center justify-between">
          <BaseFormLabel>{label}</BaseFormLabel>

          <BaseFormControl>
            <BaseSwitch checked={field.value} onCheckedChange={field.onChange} />
          </BaseFormControl>
        </BaseFormItem>
      )}
    />
  );
};
