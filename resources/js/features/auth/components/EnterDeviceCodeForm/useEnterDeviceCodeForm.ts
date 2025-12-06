import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

export function useEnterDeviceCodeForm() {
  const { t } = useTranslation();

  const [isNavigating, setIsNavigating] = useState(false);

  const formSchema = z.object({
    // 9 chars = 8 alphanumeric + 1 dash (XXXX-XXXX format).
    userCode: z.string().min(9, {
      message: t('Your one-time code must be {{val, number}} characters.', { val: 8 }),
    }),
  });
  type FormValues = z.infer<typeof formSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      userCode: '',
    },
  });

  const onSubmit = (formValues: FormValues) => {
    setIsNavigating(true);

    router.visit(
      route('passport.device.authorizations.authorize', {
        user_code: formValues.userCode.replace('-', ''),
      }),
      {
        onError: (errors) => {
          // Set the first error on the userCode field.
          const errorMessage = errors.user_code ?? Object.values(errors)[0];
          if (errorMessage) {
            form.setError('userCode', { message: errorMessage });
          }
        },
        onFinish: () => {
          setIsNavigating(false);
        },
      },
    );
  };

  return { form, isNavigating, onSubmit };
}
