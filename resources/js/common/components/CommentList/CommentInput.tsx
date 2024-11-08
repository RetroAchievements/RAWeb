import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';
import TextareaAutosize from 'react-textarea-autosize';

import { usePageProps } from '@/common/hooks/usePageProps';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { BaseButton } from '../+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
  BaseFormProvider,
} from '../+vendor/BaseForm';
import { baseTextareaClassNames } from '../+vendor/BaseTextarea';
import { UserAvatar } from '../UserAvatar';
import { useCommentListContext } from './CommentListContext';
import { useSubmitCommentForm } from './useSubmitCommentForm';

export const CommentInput: FC = () => {
  const { auth } = usePageProps();

  const { t } = useLaravelReactI18n();

  const { canComment, commentableId, commentableType, onSubmitSuccess } = useCommentListContext();

  const { form, mutation, onSubmit } = useSubmitCommentForm({
    commentableId,
    commentableType,
    onSubmitSuccess,
  });

  if (!auth?.user || !canComment) {
    return null;
  }

  return (
    <div className="flex items-start gap-4 p-2">
      <div className="mt-1">
        <UserAvatar {...auth.user} showLabel={false} />
      </div>

      <BaseFormProvider {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="w-full">
          <div className="flex flex-col">
            <BaseFormField
              control={form.control}
              name="body"
              render={({ field }) => (
                <BaseFormItem>
                  <BaseFormLabel className="sr-only">{t('Comment')}</BaseFormLabel>
                  <BaseFormControl>
                    <TextareaAutosize
                      placeholder={t(
                        'Type your comment here. Do not post or request any links to copyrighted ROMs.',
                      )}
                      maxLength={2000}
                      minRows={3}
                      className={baseTextareaClassNames}
                      {...field}
                    />
                  </BaseFormControl>

                  <BaseFormDescription>
                    <span>
                      {field.value.length}
                      {' / '}
                      {formatNumber(2000)}
                    </span>
                  </BaseFormDescription>

                  <BaseFormMessage />
                </BaseFormItem>
              )}
            />

            <div className="-mt-3 flex justify-end">
              <BaseButton type="submit" disabled={mutation.isPending || !form.formState.isValid}>
                {t('Submit')}
              </BaseButton>
            </div>
          </div>
        </form>
      </BaseFormProvider>
    </div>
  );
};
