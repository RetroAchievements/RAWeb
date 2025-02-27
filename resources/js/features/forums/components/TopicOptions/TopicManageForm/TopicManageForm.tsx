import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormProvider,
} from '@/common/components/+vendor/BaseForm';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTopicManageForm } from './useTopicManageForm';

export const TopicManageForm: FC = () => {
  const { forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useTopicManageForm(forumTopic);

  return (
    <BaseFormProvider {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className="flex flex-col gap-2"
        name="manage-topic"
      >
        <BaseFormField
          control={form.control}
          name="permissions"
          render={({ field }) => (
            <BaseFormItem className="flex flex-col gap-2 md:max-w-72">
              <BaseFormLabel>{t('Minimum Permissions')}</BaseFormLabel>
              <BaseFormControl>
                <BaseSelect onValueChange={field.onChange} defaultValue={String(field.value)}>
                  <BaseSelectTrigger>
                    <BaseSelectValue />
                  </BaseSelectTrigger>

                  <BaseSelectContent>
                    <BaseSelectItem value="0">{t('Unregistered')}</BaseSelectItem>
                    <BaseSelectItem value="1">{t('Registered')}</BaseSelectItem>
                    <BaseSelectItem value="2">{t('Junior Developer')}</BaseSelectItem>
                    <BaseSelectItem value="3">{t('Developer')}</BaseSelectItem>
                    <BaseSelectItem value="4">{t('Moderator')}</BaseSelectItem>
                  </BaseSelectContent>
                </BaseSelect>
              </BaseFormControl>
            </BaseFormItem>
          )}
        />

        <div>
          <BaseButton size="sm" disabled={!form.formState.isValid || mutation.isPending}>
            {t('Submit')}
          </BaseButton>
        </div>
      </form>
    </BaseFormProvider>
  );
};
