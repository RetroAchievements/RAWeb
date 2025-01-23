import { type FC, useId } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslationKey } from '@/types/i18next';

import type { FormValues as ProfileSectionFormValues } from './useProfileSectionForm';

export const VisibleRoleField: FC = () => {
  const { auth, displayableRoles } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const form = useFormContext<ProfileSectionFormValues>();

  const visibleRoleFieldId = useId();

  if (!displayableRoles?.length || displayableRoles.length <= 1) {
    return (
      <div className="flex w-full flex-col @xl:flex-row @xl:items-center">
        <label id={visibleRoleFieldId} className="text-menu-link @xl:w-2/5">
          {t('Visible Role')}
        </label>
        <p aria-labelledby={visibleRoleFieldId}>
          {auth?.user.visibleRole ? (
            t(auth.user.visibleRole.name as TranslationKey)
          ) : (
            <span className="italic">{t('none')}</span>
          )}
        </p>
      </div>
    );
  }

  const sortedDisplayableRoles = displayableRoles.sort((a, b) => {
    const aName = t(a.name as TranslationKey);
    const bName = t(b.name as TranslationKey);

    return aName.localeCompare(bName);
  });

  return (
    <BaseFormField
      control={form.control}
      name="visibleRoleId"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
          <BaseFormLabel htmlFor="visible-role-select" className="text-menu-link @xl:w-2/5">
            {t('Visible Role')}
          </BaseFormLabel>

          <div className="flex flex-grow flex-col gap-1">
            <BaseFormControl>
              <BaseSelect onValueChange={field.onChange} defaultValue={String(field.value)}>
                <BaseSelectTrigger id="visible-role-select">
                  <BaseSelectValue />
                </BaseSelectTrigger>

                <BaseSelectContent>
                  {sortedDisplayableRoles.map((role) => (
                    <BaseSelectItem key={`role-option-${role.id}`} value={String(role.id)}>
                      {t(role.name as TranslationKey)}
                    </BaseSelectItem>
                  ))}
                </BaseSelectContent>
              </BaseSelect>
            </BaseFormControl>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
