import type { FC } from 'react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseAutosizeTextarea } from '@/common/components/+vendor/BaseAutosizeTextarea';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { ShortcodePanel } from '@/common/components/ShortcodePanel';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useSubmitOnMetaEnter } from '@/common/hooks/useSubmitOnMetaEnter';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { useUpsertPostForm } from '../../../hooks/useUpsertPostForm';

interface EditPostFormProps {
  onPreview: (content: string) => void;
}

export const EditPostForm: FC<EditPostFormProps> = ({ onPreview }) => {
  const { forumTopicComment } = usePageProps<App.Data.EditForumTopicCommentPageProps>();
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useUpsertPostForm(
    { targetComment: forumTopicComment },
    { body: forumTopicComment.body, postAsUserId: 'self' },
  );

  const [watchedBody] = form.watch(['body']);

  const formRef = useRef<HTMLFormElement>(null);
  useSubmitOnMetaEnter({
    formRef,
    onSubmit: () => form.handleSubmit(onSubmit)(),
    isEnabled: form.formState.isValid && !mutation.isPending,
  });

  return (
    <BaseForm {...form}>
      <form ref={formRef} onSubmit={form.handleSubmit(onSubmit)}>
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
                    placeholder={t(
                      "Don't ask for links to copyrighted ROMs. Don't share links to copyrighted ROMs.",
                    )}
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
                current: getStringByteCount(watchedBody),
                max: 60_000,
              })}
            </span>

            <div className="flex gap-3">
              <BaseButton
                type="button"
                onClick={() => onPreview(watchedBody)}
                disabled={!watchedBody.length}
              >
                {t('Preview')}
              </BaseButton>

              <BaseButton type="submit" disabled={!form.formState.isValid || mutation.isPending}>
                {t('Submit')}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseForm>
  );
};
