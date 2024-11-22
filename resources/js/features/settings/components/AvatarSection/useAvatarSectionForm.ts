import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { useResetNavbarUserPic } from '../../hooks/useResetNavbarUserPic';

interface FormValues {
  imageData: File;
}

export function useAvatarSectionForm() {
  const { t } = useTranslation();

  const form = useForm<FormValues>();

  const mutation = useMutation({
    mutationFn: async (formValues: FormValues) => {
      const base64ImageData = await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result as string);
        reader.onerror = (error) => reject(error);
        reader.readAsDataURL(formValues.imageData);
      });

      const formData = new FormData();
      formData.append('imageData', base64ImageData);

      return axios.post(route('api.user.avatar.store'), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    },
  });

  const { resetNavbarUserPic } = useResetNavbarUserPic();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Uploading new avatar...'),
      success: () => {
        resetNavbarUserPic();

        return t('Uploaded!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
