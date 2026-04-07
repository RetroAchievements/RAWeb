import type { FC } from 'react';
import { useRef, useState } from 'react';
import { useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { getUserIntlLocale } from '@/common/utils/getUserIntlLocale';
import { isValidScreenshotResolution } from '@/common/utils/isValidScreenshotResolution';

import { ScreenshotDropZone } from './ScreenshotDropZone';
import { useGameScreenshotUploadForm } from './useGameScreenshotUploadForm';

const ALLOWED_MIME_TYPES_PNG_ONLY = ['image/png'];
const ALLOWED_MIME_TYPES_ALL = ['image/png', 'image/jpeg', 'image/webp'];

interface UploadFormProps {
  gameId: number;
  screenshotResolutions: Array<{ width: number; height: number }>;
  selectedType: 'title' | 'ingame' | 'completion';

  hasAnalogTvOutput?: boolean;
  supportsUpscaledScreenshots?: boolean;
  onSuccess?: (screenshot: App.Platform.Data.GameScreenshot) => void;
}

export const UploadForm: FC<UploadFormProps> = ({
  gameId,
  screenshotResolutions,
  selectedType,
  hasAnalogTvOutput,
  supportsUpscaledScreenshots,
  onSuccess,
}) => {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const locale = getUserIntlLocale(auth?.user);

  const { form, mutation, onSubmit } = useGameScreenshotUploadForm({
    gameId,
    screenshotResolutions,
    hasAnalogTvOutput,
    supportsUpscaledScreenshots,
  });

  const [previewDimensions, setPreviewDimensions] = useState<{
    width: number;
    height: number;
  } | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const imageData = useWatch({ name: 'imageData', control: form.control });

  // Keep the form's type value in sync with the parent's selection.
  const currentType = useWatch({ name: 'type', control: form.control });
  if (currentType !== selectedType) {
    form.setValue('type', selectedType);
  }

  const processFile = (file: File | undefined) => {
    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
    }

    if (!file) {
      setPreviewDimensions(null);
      setPreviewUrl(null);

      return;
    }

    const url = URL.createObjectURL(file);
    setPreviewUrl(url);

    const img = new Image();
    img.onload = () => {
      setPreviewDimensions({ width: img.naturalWidth, height: img.naturalHeight });
    };
    img.onerror = () => {
      setPreviewDimensions(null);
    };
    img.src = url;
  };

  const allowedMimeTypes = supportsUpscaledScreenshots
    ? ALLOWED_MIME_TYPES_ALL
    : ALLOWED_MIME_TYPES_PNG_ONLY;

  const isAllowedMimeType = (file: File): boolean => {
    return allowedMimeTypes.includes(file.type);
  };

  const mimeTypeErrorMessage = supportsUpscaledScreenshots
    ? t('Only PNG, JPEG, and WebP screenshots are accepted.')
    : t('This system only accepts PNG screenshots.');

  const handleDrop = (e: React.DragEvent) => {
    const file = e.dataTransfer.files[0];

    if (!isAllowedMimeType(file)) {
      toastMessage.error(mimeTypeErrorMessage);

      return;
    }

    form.setValue('imageData', file);
    processFile(file);
  };

  const handleFileChange = (file: File | undefined) => {
    if (file && !isAllowedMimeType(file)) {
      toastMessage.error(mimeTypeErrorMessage);

      return;
    }

    form.setValue('imageData', file as File);
    processFile(file);
  };

  const isResolutionValid = !!(
    previewDimensions &&
    isValidScreenshotResolution(
      previewDimensions.width,
      previewDimensions.height,
      screenshotResolutions,
      hasAnalogTvOutput,
      supportsUpscaledScreenshots,
    )
  );

  const formattedResolutions =
    screenshotResolutions.length > 0
      ? new Intl.ListFormat(locale, { style: 'narrow', type: 'conjunction' }).format(
          screenshotResolutions.map((r) => `${r.width}x${r.height}`),
        )
      : '';

  const handleFormSubmit = async (values: Parameters<typeof onSubmit>[0]) => {
    await onSubmit(values, (screenshot) => {
      setPreviewDimensions(null);
      URL.revokeObjectURL(previewUrl!);
      setPreviewUrl(null);
      onSuccess?.(screenshot);
    });
  };

  const hasPreview = !!imageData && !!previewUrl;

  return (
    <BaseForm {...form}>
      <form onSubmit={form.handleSubmit(handleFormSubmit)} className="flex flex-col gap-4">
        {/* Hidden type field, controlled by parent's slot selection. */}
        <input type="hidden" {...form.register('type')} />

        <BaseFormField
          control={form.control}
          name="imageData"
          render={() => (
            <BaseFormItem>
              <BaseFormControl>
                <ScreenshotDropZone
                  fileInputRef={fileInputRef}
                  formattedResolutions={formattedResolutions}
                  hasPreview={hasPreview}
                  isResolutionValid={isResolutionValid}
                  previewDimensions={previewDimensions}
                  previewUrl={previewUrl}
                  supportsUpscaledScreenshots={supportsUpscaledScreenshots}
                  onDrop={handleDrop}
                  onFileChange={handleFileChange}
                />
              </BaseFormControl>

              <BaseFormMessage />
            </BaseFormItem>
          )}
        />

        <BaseButton type="submit" className="w-full" disabled={!imageData || mutation.isPending}>
          {t('Submit Screenshot')}
        </BaseButton>
      </form>
    </BaseForm>
  );
};
