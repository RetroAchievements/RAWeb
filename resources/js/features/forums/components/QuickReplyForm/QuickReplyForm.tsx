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
import { ShortcodePanel } from '@/common/components/ShortcodePanel';
import { usePageProps } from '@/common/hooks/usePageProps';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { useUpsertPostForm } from '../../hooks/useUpsertPostForm';

interface QuickReplyFormProps {
  onPreview: (content: string) => void;
}

export const QuickReplyForm: FC<QuickReplyFormProps> = ({ onPreview }) => {
  const { auth, forumTopic } = usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useUpsertPostForm({ targetTopic: forumTopic }, { body: '' });
  const [body] = form.watch(['body']);

  if (!auth?.user) {
    return null;
  }

  return (
    <BaseFormProvider {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className="rounded-lg bg-embed p-2 sm:p-4"
        name="quick-reply"
      >
        <div className="flex flex-col gap-3">
          <ShortcodePanel className="p-0" />

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
                    minHeight={142}
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
