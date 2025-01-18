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
import { usePageProps } from '@/common/hooks/usePageProps';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { ShortcodePanel } from '../../ShortcodePanel';
import { useEditPostForm } from './useEditPostForm';

interface EditPostFormProps {
  onPreview: (content: string) => void;
}

export const EditPostForm: FC<EditPostFormProps> = ({ onPreview }) => {
  const { forumTopicComment } = usePageProps<App.Data.EditForumTopicCommentPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useEditPostForm(forumTopicComment, {
    body: forumTopicComment.body,
  });

  const [body] = form.watch(['body']);

  return (
    <BaseFormProvider {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col gap-3">
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
                    placeholder="Don't ask for links to copyrighted ROMs. Don't share links to copyrighted ROMs."
                    maxLength={60_000}
                    minHeight={308}
                    {...field}
                  />
                </BaseFormControl>

                <BaseFormMessage />
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

              <BaseButton type="submit" disabled={!body.length || mutation.isPending}>
                {t('Submit')}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseFormProvider>
  );
};
