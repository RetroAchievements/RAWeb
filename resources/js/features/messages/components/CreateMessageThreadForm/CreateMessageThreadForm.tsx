import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseAutosizeTextarea } from '@/common/components/+vendor/BaseAutosizeTextarea';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormProvider,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { BaseSelectAsync } from '@/common/components/+vendor/BaseSelectAsync';
import { ShortcodePanel } from '@/common/components/ShortcodePanel';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useUserSearchQuery } from '@/common/hooks/useUserSearchQuery';
import { getStringByteCount } from '@/common/utils/getStringByteCount';

import { TemplateKindAlert } from '../TemplateKindAlert';
import { useCreateMessageThreadForm } from './useCreateMessageThreadForm';

interface CreateMessageThreadFormProps {
  onPreview: (content: string) => void;
}

export const CreateMessageThreadForm: FC<CreateMessageThreadFormProps> = ({ onPreview }) => {
  const { auth, message, subject, templateKind, senderUserDisplayName, toUser } =
    usePageProps<App.Community.Data.MessageThreadCreatePageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useCreateMessageThreadForm(
    {
      title: subject ?? '',
      body: message ?? '',
      recipient: toUser?.displayName,
    },
    senderUserDisplayName,
  );
  const [body] = form.watch(['body']);

  const query = useUserSearchQuery({ initialSearchTerm: toUser?.displayName ?? '' });

  return (
    <BaseFormProvider {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col gap-5">
          <div className="flex flex-col gap-5 md:w-full md:flex-row">
            <BaseFormField
              control={form.control}
              name="recipient"
              render={({ field }) => (
                <BaseFormItem className="flex flex-col gap-1">
                  <BaseFormLabel>{t('Recipient')}</BaseFormLabel>
                  <BaseFormControl>
                    <BaseSelectAsync<App.Data.User>
                      query={query}
                      noResultsMessage={t('No users found.')}
                      popoverPlaceholder={t('type a username...')}
                      value={field.value}
                      triggerClassName="md:w-[320px] md:max-w-[320px]"
                      onChange={field.onChange}
                      width={320}
                      placeholder={t('find a user...')}
                      selectedOption={toUser ?? null}
                      disabled={!!toUser}
                      getOptionValue={(user) => user.displayName}
                      getDisplayValue={(user) => (
                        <div className="flex items-center gap-2">
                          <img className="size-6 rounded-sm" src={user.avatarUrl} />
                          <span className="font-medium">{user.displayName}</span>
                        </div>
                      )}
                      renderOption={(user) => (
                        <div className="flex items-center gap-2">
                          <img className="size-6 rounded-sm" src={user.avatarUrl} />
                          <span className="font-medium">{user.displayName}</span>
                        </div>
                      )}
                    />
                  </BaseFormControl>
                </BaseFormItem>
              )}
            />

            <BaseFormField
              control={form.control}
              name="title"
              render={({ field }) => (
                <BaseFormItem className="flex flex-col gap-1 md:flex-grow">
                  <BaseFormLabel>{t('Subject')}</BaseFormLabel>
                  <BaseFormControl>
                    <BaseInput
                      {...field}
                      required={true}
                      disabled={!!subject}
                      placeholder={t("enter your message's subject...")}
                    />
                  </BaseFormControl>
                </BaseFormItem>
              )}
            />
          </div>

          {templateKind ? <TemplateKindAlert templateKind={templateKind} /> : null}

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
                current: getStringByteCount(body),
                max: 60_000,
              })}
            </span>

            <div className="flex gap-3">
              <BaseButton type="button" onClick={() => onPreview(body)} disabled={!body.length}>
                {t('Preview')}
              </BaseButton>

              <BaseButton type="submit" disabled={!form.formState.isValid || mutation.isPending}>
                {auth!.user.displayName === senderUserDisplayName
                  ? t('Submit')
                  : t('Submit (as {{username}})', { username: senderUserDisplayName })}
              </BaseButton>
            </div>
          </div>
        </div>
      </form>
    </BaseFormProvider>
  );
};
