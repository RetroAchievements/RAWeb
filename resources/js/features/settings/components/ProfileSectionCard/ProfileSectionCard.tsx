import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { FC } from 'react';
import { useId } from 'react';
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

import { usePageProps } from '../../hooks/usePageProps';
import { SectionFormCard } from '../SectionFormCard';
import { useProfileSectionForm } from './useProfileSectionForm';

export const ProfileSectionCard: FC = () => {
  const { auth, can, userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const {
    form,
    mutation: formMutation,
    onSubmit,
  } = useProfileSectionForm({
    motto: userSettings.motto ?? '',
    userWallActive: userSettings.userWallActive ?? false,
  });

  const deleteAllCommentsMutation = useMutation({
    mutationFn: async () => {
      // This should never happen, but is here for type safety.
      if (!auth?.user.id) {
        toastMessage.error('Something went wrong.');

        return;
      }

      return axios.delete(route('user.comment.destroyAll', auth?.user.id));
    },
  });

  const visibleRoleFieldId = useId();

  const handleDeleteAllCommentsClick = () => {
    if (!confirm('Are you sure you want to permanently delete all comments on your wall?')) {
      return;
    }

    toastMessage.promise(deleteAllCommentsMutation.mutateAsync(), {
      loading: 'Deleting...',
      success: 'Successfully deleted all comments on your wall.',
      error: 'Something went wrong.',
    });
  };

  return (
    <SectionFormCard
      headingLabel="Profile"
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={formMutation.isPending}
    >
      <div className="flex flex-col gap-7 @container @xl:gap-5">
        <div className="flex w-full flex-col @xl:flex-row @xl:items-center">
          <label id={visibleRoleFieldId} className="text-menu-link @xl:w-2/5">
            Visible Role
          </label>
          <p aria-labelledby={visibleRoleFieldId}>
            {userSettings.visibleRole ? (
              `${userSettings.visibleRole}`
            ) : (
              <span className="italic">none</span>
            )}
          </p>
        </div>

        <BaseFormField
          control={form.control}
          name="motto"
          disabled={!can.updateMotto}
          render={({ field }) => (
            <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
              <BaseFormLabel className="text-menu-link @xl:w-2/5">User Motto</BaseFormLabel>

              <div className="flex flex-grow flex-col gap-1">
                <BaseFormControl>
                  <BaseInput maxLength={50} placeholder="enter a motto here..." {...field} />
                </BaseFormControl>

                <BaseFormDescription className="flex w-full justify-between">
                  {can.updateMotto ? (
                    <>
                      <span>No profanity.</span>
                      <span>{field.value.length}/50</span>
                    </>
                  ) : (
                    <span>Verify your email to update your motto.</span>
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
                Allow Comments on My User Wall
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
          <LuAlertCircle className="h-4 w-4" /> Delete All Comments on My User Wall
        </BaseButton>
      </div>
    </SectionFormCard>
  );
};
