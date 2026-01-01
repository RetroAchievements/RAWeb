import { type FC, useId } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseFormControl, BaseFormField } from '@/common/components/+vendor/BaseForm';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';
import type { TranslatedString } from '@/types/i18next';

import type { FormValues as NotificationsSectionFormValues } from './useNotificationsSectionForm';

type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];

interface NotificationsTableRowProps {
  t_label: TranslatedString;

  emailFieldName?: UserPreferenceValue;

  /**
   * When true, the checkbox value is inverted. This is useful for "opt-out"
   * preferences where the bit being SET means the user does not want emails.
   */
  isInverted?: boolean;
}

export const NotificationsSmallRow: FC<NotificationsTableRowProps> = ({
  t_label,
  emailFieldName,
  isInverted = false,
}) => {
  const { t } = useTranslation();

  const { control } = useFormContext<NotificationsSectionFormValues>();

  const emailId = useId();

  return (
    <div className="flex flex-col gap-1">
      <p className="text-menu-link">{t_label}</p>

      <div className="flex items-center gap-6">
        {emailFieldName ? (
          <BaseFormField
            control={control}
            name={emailFieldName}
            render={({ field }) => {
              const displayValue = isInverted ? !field.value : field.value;

              const handleChange = (checked: boolean) => {
                field.onChange(isInverted ? !checked : checked);
              };

              return (
                <div className="flex items-center gap-2">
                  <BaseFormControl>
                    <BaseCheckbox
                      id={emailId}
                      checked={displayValue}
                      onCheckedChange={handleChange}
                    />
                  </BaseFormControl>

                  <BaseLabel htmlFor={emailId}>{t('Email me')}</BaseLabel>
                </div>
              );
            }}
          />
        ) : null}
      </div>
    </div>
  );
};
