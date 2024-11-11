import { type FC, useId } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseFormControl, BaseFormField } from '@/common/components/+vendor/BaseForm';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import type { FormValues as NotificationsSectionFormValues } from './useNotificationsSectionForm';

type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];

interface NotificationsTableRowProps {
  t_label: string;

  emailFieldName?: UserPreferenceValue;
  siteFieldName?: UserPreferenceValue;
}

export const NotificationsSmallRow: FC<NotificationsTableRowProps> = ({
  t_label,
  emailFieldName,
  siteFieldName,
}) => {
  const { t } = useTranslation();

  const { control } = useFormContext<NotificationsSectionFormValues>();

  const emailId = useId();
  const siteId = useId();

  return (
    <div className="flex flex-col gap-1">
      <p className="text-menu-link">{t_label}</p>

      <div className="flex items-center gap-6">
        {emailFieldName ? (
          <BaseFormField
            control={control}
            name={emailFieldName}
            render={({ field }) => (
              <div className="flex items-center gap-2">
                <BaseFormControl>
                  <BaseCheckbox
                    id={emailId}
                    checked={field.value}
                    onCheckedChange={field.onChange}
                  />
                </BaseFormControl>

                <BaseLabel htmlFor={emailId}>{t('Email me')}</BaseLabel>
              </div>
            )}
          />
        ) : null}

        {siteFieldName ? (
          <BaseFormField
            control={control}
            name={siteFieldName}
            render={({ field }) => (
              <div className="flex items-center gap-2">
                <BaseFormControl>
                  <BaseCheckbox
                    id={siteId}
                    checked={field.value}
                    onCheckedChange={field.onChange}
                  />
                </BaseFormControl>

                <BaseLabel htmlFor={siteId}>{t('Notify me on the site')}</BaseLabel>
              </div>
            )}
          />
        ) : null}
      </div>
    </div>
  );
};
