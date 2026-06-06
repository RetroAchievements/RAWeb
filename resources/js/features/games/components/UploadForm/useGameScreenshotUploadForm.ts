import { zodResolver } from '@hookform/resolvers/zod';
import type { AxiosError } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { getIsValidScreenshotResolution } from '@/common/utils/getIsValidScreenshotResolution';

import { useSubmitGameScreenshotMutation } from '../../hooks/mutations/useSubmitGameScreenshotMutation';

const formSchema = z.object({
  imageData: z.instanceof(File),
  type: z.enum(['title', 'ingame', 'completion']),
});
type FormValues = z.infer<typeof formSchema>;

interface UseGameScreenshotUploadFormOptions {
  gameId: number;
  screenshotResolutions: Array<{ width: number; height: number }>;

  hasAnalogTvOutput?: boolean;
  supportsUpscaledScreenshots?: boolean;
}

export function useGameScreenshotUploadForm({
  gameId,
  hasAnalogTvOutput,
  screenshotResolutions,
  supportsUpscaledScreenshots,
}: UseGameScreenshotUploadFormOptions) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      type: 'ingame',
    },
  });

  const mutation = useSubmitGameScreenshotMutation();

  const onSubmit = async (
    formValues: FormValues,
    onSuccess?: (screenshot: App.Platform.Data.GameScreenshot) => void,
  ) => {
    // Step 1: Read the image dimensions.
    const { width, height } = await getImageDimensions(formValues.imageData);

    // Step 2: Validate the resolution client-side.
    if (
      !getIsValidScreenshotResolution(
        width,
        height,
        screenshotResolutions,
        hasAnalogTvOutput,
        supportsUpscaledScreenshots,
      )
    ) {
      form.setError('imageData', {
        message: t("Resolution doesn't match. See the preview above."),
      });

      return;
    }

    // Step 3: Submit the image to the back-end as a pending screenshot.
    const formData = new FormData();
    formData.append('file', formValues.imageData);
    formData.append('type', formValues.type);

    const errorMessages: Record<string, string> = {
      duplicate_hash: t('This image has already been uploaded for this game.'),
      invalid_resolution: t(
        "This screenshot's resolution doesn't match what's expected for this system.",
      ),
      pending_cap_reached: t('You have reached the maximum number of pending submissions.'),
    };

    toastMessage.promise(mutation.mutateAsync({ gameId, formData }), {
      loading: t('Submitting...'),
      success: (response) => {
        form.reset({ type: formValues.type });
        onSuccess?.(response.data as App.Platform.Data.GameScreenshot);

        return t('Screenshot submitted successfully!');
      },
      error: (error: AxiosError<{ error?: string }>) => {
        const errorCode = error.response?.data?.error;

        return (errorCode && errorMessages[errorCode]) || t('Something went wrong.');
      },
    });
  };

  return { form, mutation, onSubmit };
}

function getImageDimensions(file: File): Promise<{ width: number; height: number }> {
  return new Promise((resolve) => {
    const url = URL.createObjectURL(file);
    const img = new Image();

    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve({ width: img.naturalWidth, height: img.naturalHeight });
    };

    img.src = url;
  });
}
