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
  t_label: string;
  fieldName: UserPreferenceValue;
  control: Control<PreferencesSectionFormValues>;

  /**
   * If true, the switch will show as enabled when the setting is turned off.
   */
  isSwitchInverted?: boolean;
}

export const PreferencesSwitchField: FC<PreferencesTableRowProps> = ({
  t_label,
  fieldName,
  control,
  isSwitchInverted = false,
}) => {
  return (
    <BaseFormField
      control={control}
      name={fieldName as keyof PreferencesSectionFormValues}
      render={({ field }) => (
        <BaseFormItem className="flex w-full items-center justify-between">
          <BaseFormLabel>{t_label}</BaseFormLabel>

          <BaseFormControl>
            <BaseSwitch
              checked={isSwitchInverted ? !field.value : field.value}
              onCheckedChange={(newValue) => {
                field.onChange(isSwitchInverted ? !newValue : newValue);
              }}
            />
          </BaseFormControl>
        </BaseFormItem>
      )}
    />
  );
};
