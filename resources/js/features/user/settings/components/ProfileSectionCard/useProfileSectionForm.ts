import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

const profileFormSchema = z.object({
  motto: z.string().max(50),
  userWallActive: z.boolean(),
});

type FormValues = z.infer<typeof profileFormSchema>;

export function useProfileSectionForm(initialValues: FormValues) {
  const form = useForm<FormValues>({
    resolver: zodResolver(profileFormSchema),
    defaultValues: initialValues,
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('settings.profile.update'), formValues);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: 'Updating...',
      success: 'Updated.',
      error: 'Something went wrong.',
    });
  };

  return { form, mutation, onSubmit };
}
