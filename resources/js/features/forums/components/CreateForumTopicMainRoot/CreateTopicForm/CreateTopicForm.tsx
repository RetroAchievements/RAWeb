import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseAutosizeTextarea } from '@/common/components/+vendor/BaseAutosizeTextarea';
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
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { ShortcodePanel } from '../../ShortcodePanel';
import { useCreateTopicForm } from './useCreateTopicForm';

interface CreateTopicFormProps {
  onPreview: (content: string) => void;
}

export const CreateTopicForm: FC<CreateTopicFormProps> = ({ onPreview }) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useCreateTopicForm();

  const [body] = form.watch(['body']);

  return (
    <BaseFormProvider {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col gap-3">
          <BaseFormField
            control={form.control}
            name="title"
            render={({ field }) => (
              <BaseFormItem className="flex flex-col gap-1">
                <BaseFormLabel>{t('Title')}</BaseFormLabel>
                <BaseFormControl>
                  <BaseInput
                    {...field}
                    required
                    placeholder={t("enter your new topic's title...")}
                  />
                </BaseFormControl>

                <BaseFormMessage />
              </BaseFormItem>
            )}
          />

          <ShortcodePanel />
          <BaseFormField
            control={form.control}
            name="body"
            render={({ field }) => (
              <BaseFormItem>
                <BaseFormLabel className="sr-only">{t('Body')}</BaseFormLabel>
                <BaseFormControl>
                  <BaseAutosizeTextarea
                    className="p-3"
                    placeholder={t(
                      "Don't ask for links to copyrighted ROMs. Don't share links to copyrighted ROMs.",
                    )}
                    maxLength={60_000}
                    minHeight={308}
                    {...field}
                  />
                </BaseFormControl>
              </BaseFormItem>
            )}
          />

          <div className="flex w-full justify-between gap-3">
            <span className="text-neutral-400">
              {t('{{current, number}} / {{max, number}}', {
                current: getStringByteCount(body),
                max: 60_000,
              })}
            </span>

            <div className="flex gap-3">
              <BaseButton type="button" onClick={() => onPreview(body)} disabled={!body.length}>
                {t('Preview')}
              </BaseButton>

              <BaseButton type="submit" disabled={!form.formState.isValid || mutation.isPending}>
                {t('Submit')}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseFormProvider>
  );
};
