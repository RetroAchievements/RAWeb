import { type FC, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import TextareaAutosize from 'react-textarea-autosize';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useSubmitOnMetaEnter } from '@/common/hooks/useSubmitOnMetaEnter';

import { BaseButton } from '../+vendor/BaseButton';
import {
  BaseForm,
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '../+vendor/BaseForm';
import { baseTextareaClassNames } from '../+vendor/BaseTextarea';
import { MetaKeySubmitTooltip } from '../MetaKeySubmitTooltip';
import { UserAvatar } from '../UserAvatar';
import { useCommentListContext } from './CommentListContext';
import { useSubmitCommentForm } from './useSubmitCommentForm';

export const CommentInput: FC = () => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const { canComment, commentableId, commentableType, onSubmitSuccess } = useCommentListContext();

  const { form, mutation, onSubmit } = useSubmitCommentForm({
    commentableId,
    commentableType,
    onSubmitSuccess,
  });

  const formRef = useRef<HTMLFormElement>(null);
  useSubmitOnMetaEnter({
    formRef,
    onSubmit: () => form.handleSubmit(onSubmit)(),
    isEnabled: form.formState.isValid && !mutation.isPending,
  });

  if (!auth?.user || !canComment) {
    return null;
  }

  return (
    <div className="flex items-start gap-4 p-2">
      <div className="mt-1">
        <UserAvatar {...auth.user} showLabel={false} />
      </div>

      <BaseForm {...form}>
        <form ref={formRef} onSubmit={form.handleSubmit(onSubmit)} className="w-full">
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
                      {t('{{current, number}} / {{max, number}}', {
                        current: field.value.length,
                        max: 2000,
                      })}
                    </span>
                  </BaseFormDescription>

                  <BaseFormMessage />
                </BaseFormItem>
              )}
            />

            <div className="-mt-3 flex justify-end">
              <MetaKeySubmitTooltip>
                <BaseButton type="submit" disabled={mutation.isPending || !form.formState.isValid}>
                  {t('Submit')}
                </BaseButton>
              </MetaKeySubmitTooltip>
            </div>
          </div>
        </form>
      </BaseForm>
    </div>
  );
};
