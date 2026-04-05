import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useNsfwScanner } from '@/common/hooks/useNsfwScanner';
import { usePageProps } from '@/common/hooks/usePageProps';
import { getUserIntlLocale } from '@/common/utils/getUserIntlLocale';
import { isValidScreenshotResolution } from '@/common/utils/isValidScreenshotResolution';

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
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const locale = getUserIntlLocale(auth?.user);

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      type: 'ingame',
    },
  });

  const mutation = useSubmitGameScreenshotMutation();

  const { scanImage } = useNsfwScanner({ isEnabled: true });

  const onSubmit = async (formValues: FormValues) => {
    try {
      // Step 1: Read the image dimensions.
      const { width, height } = await getImageDimensions(formValues.imageData);

      // Step 2: Validate the resolution client-side.
      if (
        !isValidScreenshotResolution(
          width,
          height,
          screenshotResolutions,
          hasAnalogTvOutput,
          supportsUpscaledScreenshots,
        )
      ) {
        const formatted = new Intl.ListFormat(locale, {
          style: 'narrow',
          type: 'conjunction',
        }).format(screenshotResolutions.map((r) => `${r.width}x${r.height}`));

        const errorMessage = supportsUpscaledScreenshots
          ? t(
              "This screenshot's dimensions ({{width}}x{{height}}) don't match the expected resolutions: {{resolutions}} (or 2x/3x multiples).",
              { width, height, resolutions: formatted },
            )
          : t(
              "This screenshot's dimensions ({{width}}x{{height}}) don't match the expected resolutions: {{resolutions}}.",
              { width, height, resolutions: formatted },
            );

        form.setError('imageData', { message: errorMessage });

        return;
      }

      // Step 3: Do a client-side NSFW scan.
      const scanResult = await scanImage(formValues.imageData);
      if (scanResult.isNsfw) {
        form.setError('imageData', {
          message: t('This image could not be processed. Please try a different screenshot.'),
        });

        return;
      }

      // Step 4: Submit the image to the back-end as a pending screenshot.
      const formData = new FormData();
      formData.append('file', formValues.imageData);
      formData.append('type', formValues.type);

      const response = await mutation.mutateAsync({ gameId, formData });

      toastMessage.success(t('Screenshot submitted successfully!'));

      form.reset({ type: formValues.type });

      return response.data as App.Platform.Data.GameScreenshot;
    } catch {
      toastMessage.error(t('Something went wrong.'));
    }
  };

  return { form, mutation, onSubmit };
}

function getImageDimensions(file: File): Promise<{ width: number; height: number }> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();

    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve({ width: img.naturalWidth, height: img.naturalHeight });
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Failed to read image dimensions.'));
    };

    img.src = url;
  });
}
