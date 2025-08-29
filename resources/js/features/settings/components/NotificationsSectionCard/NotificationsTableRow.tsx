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
}

export const NotificationsTableRow: FC<NotificationsTableRowProps> = ({
  t_label,
  emailFieldName,
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
            render={({ field }) => (
              <>
                <BaseFormControl>
                  <BaseCheckbox
                    id={emailId}
                    checked={field.value}
                    onCheckedChange={field.onChange}
                    data-testid={`email-checkbox-${t_label.replace(/\s+/g, '-').toLowerCase()}`}
                  />
                </BaseFormControl>

                <BaseLabel htmlFor={emailId}>{t('Email me')}</BaseLabel>
              </>
            )}
          />
        ) : null}
      </td>
    </tr>
  );
};
