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

import { useCreateMessageReplyForm } from './useCreateMessageReplyForm';

interface CreateMessageReplyFormProps {
  onPreview: (content: string) => void;
}

export const CreateMessageReplyForm: FC<CreateMessageReplyFormProps> = ({ onPreview }) => {
  const { auth, senderUserAvatarUrl, senderUserDisplayName } =
    usePageProps<App.Community.Data.MessageThreadShowPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useCreateMessageReplyForm();
  const [body] = form.watch(['body']);

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
                    minHeight={144}
                    maxLength={2000}
                    placeholder={t('Enter your message here...')}
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
                max: 2000,
              })}
            </span>

            <div className="flex gap-3">
              <BaseButton type="button" onClick={() => onPreview(body)} disabled={!body.length}>
                {t('Preview')}
              </BaseButton>

              <BaseButton
                type="submit"
                className="flex items-center gap-2"
                disabled={!form.formState.isValid || mutation.isPending}
              >
                {senderUserAvatarUrl && auth!.user.displayName !== senderUserDisplayName ? (
                  <img
                    src={senderUserAvatarUrl}
                    alt={senderUserDisplayName}
                    className="size-6 rounded-full"
                    aria-hidden={true}
                  />
                ) : null}

                {auth!.user.displayName === senderUserDisplayName
                  ? t('Submit')
                  : t('Submit as {{displayName}}', { displayName: senderUserDisplayName })}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseForm>
  );
};
