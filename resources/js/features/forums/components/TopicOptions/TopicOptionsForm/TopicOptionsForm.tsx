import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
  BaseFormProvider,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTopicOptionsForm } from './useTopicOptionsForm';

export const TopicOptionsForm: FC = () => {
  const { forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useTopicOptionsForm(forumTopic);

  return (
    <BaseFormProvider {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-2">
        <BaseFormField
          control={form.control}
          name="title"
          render={({ field }) => (
            <BaseFormItem className="flex flex-col gap-2">
              <BaseFormLabel>{t('Topic Title')}</BaseFormLabel>
              <BaseFormControl>
                <BaseInput {...field} />
              </BaseFormControl>

              <BaseFormMessage />
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
