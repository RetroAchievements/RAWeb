import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateAvatarMutation } from '@/features/settings/hooks/mutations/useUpdateAvatarMutation';

import { useResetNavbarUserPic } from '../../hooks/useResetNavbarUserPic';

interface FormValues {
  imageData: File;
}

export function useAvatarSectionForm() {
  const { t } = useTranslation();

  const form = useForm<FormValues>();

  const mutation = useUpdateAvatarMutation();

  const { resetNavbarUserPic } = useResetNavbarUserPic();

  const onSubmit = (formValues: FormValues) => {
    const fileReaderPromise = new Promise<string>((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result as string);
      reader.onerror = (error) => reject(error);
      reader.readAsDataURL(formValues.imageData);
    });

    toastMessage.promise(
      fileReaderPromise.then((base64ImageData) => {
        const formData = new FormData();
        formData.append('imageData', base64ImageData);

        return mutation.mutateAsync({ formData });
      }),
      {
        loading: t('Uploading new avatar...'),
        success: () => {
          resetNavbarUserPic();

          return t('Uploaded!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
