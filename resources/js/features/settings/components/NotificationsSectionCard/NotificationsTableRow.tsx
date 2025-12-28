import { type FC, useId } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseFormControl, BaseFormField } from '@/common/components/+vendor/BaseForm';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import type { TranslatedString } from '@/types/i18next';

import type { UserPreferenceValue } from '../../models';
import type { FormValues as NotificationsSectionFormValues } from './useNotificationsSectionForm';

interface NotificationsTableRowProps {
  t_label: TranslatedString;

  emailFieldName?: UserPreferenceValue;

  /**
   * When true, the checkbox value is inverted. This is useful for "opt-out"
   * preferences where the bit being set means the user does not want emails.
   */
  isInverted?: boolean;
}

export const NotificationsTableRow: FC<NotificationsTableRowProps> = ({
  t_label,
  emailFieldName,
  isInverted = false,
}) => {
  const { t } = useTranslation();

  const { control } = useFormContext<NotificationsSectionFormValues>();

  const emailId = useId();

  return (
    <tr>
      <th scope="row">{t_label}</th>

      <td className="flex items-center justify-end gap-2">
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
                <>
                  <BaseFormControl>
                    <BaseCheckbox
                      id={emailId}
                      checked={displayValue}
                      onCheckedChange={handleChange}
                      data-testid={`email-checkbox-${t_label.replace(/\s+/g, '-').toLowerCase()}`}
                    />
                  </BaseFormControl>

                  <BaseLabel htmlFor={emailId}>{t('Email me')}</BaseLabel>
                </>
              );
            }}
          />
        ) : null}
      </td>
    </tr>
  );
};
