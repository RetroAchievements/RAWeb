import type { FC } from 'react';
import { useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useResetAvatarMutation } from '../../hooks/mutations/useResetAvatarMutation';
import { useResetNavbarUserPic } from '../../hooks/useResetNavbarUserPic';
import { SectionStandardCard } from '../SectionStandardCard';
import { useAvatarSectionForm } from './useAvatarSectionForm';

export const AvatarSection: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const { form, onSubmit, mutation: formMutation } = useAvatarSectionForm();

  const resetAvatarMutation = useResetAvatarMutation();

  const { resetNavbarUserPic } = useResetNavbarUserPic();

  const handleResetAvatarClick = () => {
    if (
      !confirm(
        t('Are you sure you want to reset your avatar to the default? This cannot be reversed.'),
      )
    ) {
      return;
    }

    toastMessage.promise(resetAvatarMutation.mutateAsync(), {
      loading: t('Resetting...'),
      success: () => {
        resetNavbarUserPic();

        return t('Reset avatar!');
      },
      error: t('Something went wrong.'),
    });
  };

  const imageData = useWatch({ name: 'imageData', control: form.control });

  return (
    <SectionStandardCard t_headingLabel={t('Avatar')}>
      <div className="flex flex-col gap-4">
        {can.updateAvatar ? (
          <>
            <p>{t('PNG, JPEG, and GIF images are supported.')}</p>

            <BaseForm {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-1">
                <div className="@container">
                  <BaseFormField
                    control={form.control}
                    name="imageData"
                    render={({ field }) => (
                      <BaseFormItem className="flex flex-col gap-2 @xl:flex-row @xl:items-center">
                        <BaseFormLabel className="text-menu-link @xl:w-2/5">
                          {t('New Image')}
                        </BaseFormLabel>

                        <div className="flex flex-grow flex-col gap-1">
                          <BaseFormControl>
                            <BaseInput
                              {...field}
                              type="file"
                              accept=".png,.jpeg,.jpg,.gif"
                              value={(field.value as File & { fileName: string })?.fileName}
                              onChange={(event) => {
                                field.onChange(event.target.files![0]);
                              }}
                            />
                          </BaseFormControl>

                          <BaseFormMessage />
                        </div>
                      </BaseFormItem>
                    )}
                  />
                </div>

                <div className="flex w-full justify-end">
                  <BaseButton type="submit" disabled={!imageData || formMutation.isPending}>
                    {t('Upload')}
                  </BaseButton>
                </div>
              </form>
            </BaseForm>

            <BaseButton
              className="gap-2 self-start"
              size="sm"
              variant="destructive"
              onClick={handleResetAvatarClick}
            >
              <LuCircleAlert className="h-4 w-4" />
              {t('Reset Avatar to Default')}
            </BaseButton>
          </>
        ) : (
          <p>
            {t(
              'To upload an avatar, earn 250 points or wait until your account is at least 14 days old.',
            )}
          </p>
        )}
      </div>
    </SectionStandardCard>
  );
};
