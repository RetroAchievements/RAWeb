import type { FC } from 'react';
import { useRef } from 'react';
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
import { BaseSelectNative } from '@/common/components/+vendor/BaseSelectNative';
import { ShortcodePanel } from '@/common/components/ShortcodePanel';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useSubmitOnMetaEnter } from '@/common/hooks/useSubmitOnMetaEnter';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { useCreateTopicForm } from './useCreateTopicForm';

interface CreateTopicFormProps {
  onPreview: (content: string) => void;
}

export const CreateTopicForm: FC<CreateTopicFormProps> = ({ onPreview }) => {
  const { t } = useTranslation();

  const { auth, accessibleTeamAccounts } = usePageProps<App.Data.CreateForumTopicPageProps>();
  const { form, mutation, onSubmit } = useCreateTopicForm();

  const [watchedBody, watchedPostAsUserId] = form.watch(['body', 'postAsUserId']);

  const watchedPostAsUser =
    watchedPostAsUserId !== 'self'
      ? accessibleTeamAccounts?.find((ta) => ta.id === Number(watchedPostAsUserId))
      : null;

  const formRef = useRef<HTMLFormElement>(null);
  useSubmitOnMetaEnter({
    formRef,
    onSubmit: () => form.handleSubmit(onSubmit)(),
    isEnabled: form.formState.isValid && !mutation.isPending,
  });

  // Sort team accounts alphabetically by display name.
  const sortedTeamAccounts = accessibleTeamAccounts
    ? [...accessibleTeamAccounts].sort((a, b) => a.displayName.localeCompare(b.displayName))
    : null;

  return (
    <BaseFormProvider {...form}>
      <form ref={formRef} onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col gap-5">
          <div className="flex w-full flex-col gap-3 md:flex-row md:gap-5">
            {sortedTeamAccounts?.length ? (
              <BaseFormField
                control={form.control}
                name="postAsUserId"
                render={({ field }) => (
                  <BaseFormItem className="flex w-full flex-col gap-1 md:w-[320px] md:min-w-[320px]">
                    <BaseFormLabel>{t('Post as')}</BaseFormLabel>

                    <BaseFormControl>
                      <BaseSelectNative {...field} className="h-10">
                        <option value="self">{auth!.user.displayName}</option>

                        {sortedTeamAccounts.map((teamAccount) => (
                          <option key={`acct-${teamAccount.id}`} value={teamAccount.id}>
                            {teamAccount.displayName}
                          </option>
                        ))}
                      </BaseSelectNative>
                    </BaseFormControl>

                    <BaseFormMessage />
                  </BaseFormItem>
                )}
              />
            ) : null}

            <BaseFormField
              control={form.control}
              name="title"
              render={({ field }) => (
                <BaseFormItem className="flex w-full flex-col gap-1">
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
          </div>

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
                </BaseFormItem>
              )}
            />
          </div>

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

              <BaseButton
                type="submit"
                className="flex items-center gap-2"
                disabled={!form.formState.isValid || mutation.isPending}
              >
                {watchedPostAsUser ? (
                  <img
                    src={watchedPostAsUser.avatarUrl}
                    alt={watchedPostAsUser.displayName}
                    className="size-6 rounded-full"
                    aria-hidden={true}
                  />
                ) : null}

                {watchedPostAsUser
                  ? t('Submit as {{displayName}}', { displayName: watchedPostAsUser.displayName })
                  : t('Submit')}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseFormProvider>
  );
};
