import { type FC, useId } from 'react';
import { useFormContext } from 'react-hook-form';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseFormControl, BaseFormField } from '@/common/components/+vendor/BaseForm';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import type { FormValues as NotificationsSectionFormValues } from './useNotificationsSectionForm';

type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];

interface NotificationsTableRowProps {
  label: string;

  emailFieldName?: UserPreferenceValue;
  siteFieldName?: UserPreferenceValue;
}

export const NotificationsTableRow: FC<NotificationsTableRowProps> = ({
  label,
  emailFieldName,
  siteFieldName,
}) => {
  const { control } = useFormContext<NotificationsSectionFormValues>();

  const emailId = useId();
  const siteId = useId();

  return (
    <tr>
      <th scope="row" className="w-[40%]">
        {label}
      </th>

      <td>
        <div className="flex items-center gap-2">
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
                      data-testid={`email-checkbox-${label.replace(/\s+/g, '-').toLowerCase()}`}
                    />
                  </BaseFormControl>

                  <BaseLabel htmlFor={emailId}>Email me</BaseLabel>
                </>
              )}
            />
          ) : null}
        </div>
      </td>

      <td>
        <div className="flex items-center gap-2">
          {siteFieldName ? (
            <BaseFormField
              control={control}
              name={siteFieldName}
              render={({ field }) => (
                <>
                  <BaseFormControl>
                    <BaseCheckbox
                      id={siteId}
                      checked={field.value}
                      onCheckedChange={field.onChange}
                      data-testid={`site-checkbox-${label.replace(/\s+/g, '-').toLowerCase()}`}
                    />
                  </BaseFormControl>

                  <BaseLabel htmlFor={siteId}>Notify me on the site</BaseLabel>
                </>
              )}
            />
          ) : null}
        </div>
      </td>
    </tr>
  );
};
