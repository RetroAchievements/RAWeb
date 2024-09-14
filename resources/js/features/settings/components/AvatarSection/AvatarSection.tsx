import type { FC } from 'react';
import { LuAlertCircle } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { baseCardTitleClassNames } from '@/common/components/+vendor/BaseCard';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
  BaseFormProvider,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { usePageProps } from '../../hooks/usePageProps';
import { useResetNavbarUserPic } from '../../hooks/useResetNavbarUserPic';
import { useAvatarSectionForm } from './useAvatarSectionForm';
import { useResetAvatarMutation } from './useResetAvatarMutation';

export const AvatarSection: FC = () => {
  const { can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { form, mutation: formMutation, onSubmit } = useAvatarSectionForm();

  const resetAvatarMutation = useResetAvatarMutation();

  const { resetNavbarUserPic } = useResetNavbarUserPic();

  const handleResetAvatarClick = () => {
    if (
      !confirm(
        'Are you sure you want to reset your avatar to the default? This cannot be reversed.',
      )
    ) {
      return;
    }

    toastMessage.promise(resetAvatarMutation.mutateAsync(), {
      loading: 'Resetting...',
      success: () => {
        resetNavbarUserPic();

        return 'Reset avatar!';
      },
      error: 'Something went wrong.',
    });
  };

  const [imageData] = form.watch(['imageData']);

  return (
    <div className="flex flex-col gap-4">
      <h3 className={baseCardTitleClassNames}>Avatar</h3>

      {can.updateAvatar ? (
        <>
          <p>Only png, jpeg, and gif files are supported.</p>

          <BaseFormProvider {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="flex flex-col gap-1">
              <div>
                <BaseFormField
                  control={form.control}
                  name="imageData"
                  render={({ field }) => (
                    <BaseFormItem className="flex flex-col gap-2">
                      <BaseFormLabel className="text-menu-link">New Image</BaseFormLabel>

                      <BaseFormControl>
                        <BaseInput
                          {...field}
                          type="file"
                          accept=".png,.jpeg,.jpg,.gif"
                          value={(field.value as File & { fileName: string })?.fileName}
                          onChange={(event) => {
                            if (event.target.files) {
                              field.onChange(event.target.files[0]);
                            }
                          }}
                        />
                      </BaseFormControl>

                      <BaseFormMessage />
                    </BaseFormItem>
                  )}
                />
              </div>

              <div className="flex w-full justify-end">
                <BaseButton type="submit" disabled={!imageData || formMutation.isPending}>
                  Upload
                </BaseButton>
              </div>
            </form>
          </BaseFormProvider>

          <p>
            After uploading, press Ctrl + F5. This refreshes your browser cache making the new image
            visible.
          </p>

          <BaseButton
            className="gap-2"
            size="sm"
            variant="destructive"
            onClick={handleResetAvatarClick}
          >
            <LuAlertCircle className="h-4 w-4" />
            Reset Avatar to Default
          </BaseButton>
        </>
      ) : (
        <p>
          To upload an avatar, earn 250 points or wait until your account is at least 14 days old.
        </p>
      )}
    </div>
  );
};
