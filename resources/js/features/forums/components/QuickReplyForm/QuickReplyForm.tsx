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
import { BaseSelectNative } from '@/common/components/+vendor/BaseSelectNative';
import { ShortcodePanel } from '@/common/components/ShortcodePanel';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useSubmitOnMetaEnter } from '@/common/hooks/useSubmitOnMetaEnter';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { useUpsertPostForm } from '../../hooks/useUpsertPostForm';

interface QuickReplyFormProps {
  onPreview: (content: string) => void;
}

export const QuickReplyForm: FC<QuickReplyFormProps> = ({ onPreview }) => {
  const { auth, forumTopic, accessibleTeamAccounts } =
    usePageProps<App.Data.ShowForumTopicPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useUpsertPostForm(
    { targetTopic: forumTopic },
    { body: '', postAsUserId: 'self' },
  );
  const [watchedBody, watchedPostAsUserId] = form.watch(['body', 'postAsUserId']);

  const watchedPostAsUser =
    watchedPostAsUserId !== 'self'
      ? accessibleTeamAccounts?.find((ta) => ta.id === Number(watchedPostAsUserId))
      : null;

  // Sort team accounts alphabetically by display name.
  const sortedTeamAccounts = accessibleTeamAccounts
    ? [...accessibleTeamAccounts].sort((a, b) => a.displayName.localeCompare(b.displayName))
    : null;

  const formRef = useRef<HTMLFormElement>(null);
  useSubmitOnMetaEnter({
    formRef,
    onSubmit: () => form.handleSubmit(onSubmit)(),
    isEnabled: form.formState.isValid && !mutation.isPending,
  });

  if (!auth?.user) {
    return null;
  }

  return (
    <BaseFormProvider {...form}>
      <form
        ref={formRef}
        onSubmit={form.handleSubmit(onSubmit)}
        className="rounded-lg bg-embed p-2 light:border light:border-neutral-300 light:bg-white sm:p-4"
        name="quick-reply"
      >
        <div className="flex flex-col gap-3">
          <div className="flex w-full flex-col gap-2 lg:flex-row lg:justify-between lg:gap-5">
            <ShortcodePanel className="p-0" />

            {sortedTeamAccounts?.length ? (
              <BaseFormField
                control={form.control}
                name="postAsUserId"
                render={({ field }) => (
                  <BaseFormItem className="flex w-full items-center gap-2 lg:w-auto">
                    <BaseFormLabel className="whitespace-nowrap">{t('Post as')}</BaseFormLabel>

                    <BaseFormControl>
                      <BaseSelectNative {...field} className="h-10 min-w-[200px] lg:h-[30px]">
                        <option value="self">{auth.user.displayName}</option>

                        {sortedTeamAccounts.map((teamAccount) => (
                          <option key={`acct-${teamAccount.id}`} value={teamAccount.id}>
                            {teamAccount.displayName}
                          </option>
                        ))}
                      </BaseSelectNative>
                    </BaseFormControl>
                  </BaseFormItem>
                )}
              />
            ) : null}
          </div>

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

                {!watchedPostAsUser && accessibleTeamAccounts?.length ? (
                  <img
                    src={auth.user.avatarUrl}
                    alt={auth.user.displayName}
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
