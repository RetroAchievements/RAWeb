import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlertCircle } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionFormCard } from '../SectionFormCard';
import { useProfileSectionForm } from './useProfileSectionForm';
import { VisibleRoleField } from './VisibleRoleField';

export const ProfileSectionCard: FC = () => {
  const { auth, can, userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const {
    form,
    onSubmit,
    mutation: formMutation,
  } = useProfileSectionForm({
    motto: userSettings.motto ?? '',
    userWallActive: userSettings.userWallActive ?? false,
    visibleRoleId: auth?.user.visibleRole ? auth.user.visibleRole.id : null,
  });

  const deleteAllCommentsMutation = useMutation({
    mutationFn: async () => {
      return axios.delete(route('user.comment.destroyAll', auth!.user.id));
    },
  });

  const handleDeleteAllCommentsClick = () => {
    if (!confirm(t('Are you sure you want to permanently delete all comments on your wall?'))) {
      return;
    }

    toastMessage.promise(deleteAllCommentsMutation.mutateAsync(), {
      loading: t('Deleting...'),
      success: t('Successfully deleted all comments on your wall.'),
      error: t('Something went wrong.'),
    });
  };

  return (
    <SectionFormCard
      t_headingLabel={t('Profile')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={formMutation.isPending}
    >
      <div className="flex flex-col gap-7 @container @xl:gap-5">
        <VisibleRoleField />

        <BaseFormField
          control={form.control}
          name="motto"
          disabled={!can.updateMotto}
          render={({ field }) => (
            <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
              <BaseFormLabel className="text-menu-link @xl:w-2/5">{t('User Motto')}</BaseFormLabel>

              <div className="flex flex-grow flex-col gap-1">
                <BaseFormControl>
                  <BaseInput maxLength={50} placeholder={t('enter a motto here...')} {...field} />
                </BaseFormControl>

                <BaseFormDescription className="flex w-full justify-between">
                  {can.updateMotto ? (
                    <>
                      <span>{t('No profanity.')}</span>
                      {/* eslint-disable-next-line react/jsx-no-literals -- this is valid */}
                      <span>{field.value.length}/50</span>
                    </>
                  ) : (
                    <span>{t('Verify your email to update your motto.')}</span>
                  )}
                </BaseFormDescription>
              </div>
            </BaseFormItem>
          )}
        />

        <BaseFormField
          control={form.control}
          name="userWallActive"
          render={({ field }) => (
            <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
              <BaseFormLabel className="text-menu-link @xl:w-2/5">
                {t('Allow Comments on My User Wall')}
              </BaseFormLabel>

              <BaseFormControl>
                <BaseSwitch checked={field.value} onCheckedChange={field.onChange} />
              </BaseFormControl>
            </BaseFormItem>
          )}
        />

        <BaseButton
          className="flex w-full gap-2 @lg:max-w-fit"
          type="button"
          size="sm"
          variant="destructive"
          onClick={handleDeleteAllCommentsClick}
        >
          <LuAlertCircle className="h-4 w-4" /> {t('Delete All Comments on My User Wall')}
        </BaseButton>
      </div>
    </SectionFormCard>
  );
};
