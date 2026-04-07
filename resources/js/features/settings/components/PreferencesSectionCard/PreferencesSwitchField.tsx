import { type FC } from 'react';
import type { Control } from 'react-hook-form';
import { LuInfo } from 'react-icons/lu';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';
import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';
import type { TranslatedString } from '@/types/i18next';

import type { FormValues as PreferencesSectionFormValues } from './usePreferencesSectionForm';

type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];

interface PreferencesTableRowProps {
  t_label: TranslatedString;
  fieldName: UserPreferenceValue | 'hasBetaFeatures';
  control: Control<PreferencesSectionFormValues>;

  /**
   * If true, the switch will show as enabled when the setting is turned off.
   */
  isSwitchInverted?: boolean;

  t_infoText?: TranslatedString;
}

export const PreferencesSwitchField: FC<PreferencesTableRowProps> = ({
  t_label,
  fieldName,
  control,
  isSwitchInverted = false,
  t_infoText = null,
}) => {
  return (
    <BaseFormField
      control={control}
      name={fieldName as keyof PreferencesSectionFormValues}
      render={({ field }) => (
        <BaseFormItem className="flex w-full items-center justify-between gap-1">
          <div className="flex items-center gap-1">
            <BaseFormLabel>{t_label}</BaseFormLabel>

            {t_infoText ? (
              <BaseTooltip>
                <BaseTooltipTrigger>
                  <LuInfo
                    className={cn(
                      'hidden size-4 text-neutral-500 transition hover:text-neutral-300 sm:inline',
                      'light:text-neutral-700',
                    )}
                  />
                </BaseTooltipTrigger>

                <BaseTooltipContent className="max-w-72 font-normal leading-normal">
                  <span className="text-xs font-normal">{t_infoText}</span>
                </BaseTooltipContent>
              </BaseTooltip>
            ) : null}
          </div>

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
