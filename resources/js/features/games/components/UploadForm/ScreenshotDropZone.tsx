import * as m from 'motion/react-m';
import type { DragEvent, FC, RefObject } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuUpload } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

import { ScreenshotPreviewMeta } from './ScreenshotPreviewMeta';

interface ScreenshotDropZoneProps {
  fileInputRef: RefObject<HTMLInputElement | null>;
  isResolutionValid: boolean;
  previewUrl: string | null;

  hasConsistencyWarning?: boolean;
  hasPreview?: boolean;
  is1xCapture?: boolean;
  onDrop?: (e: DragEvent) => void;
  onFileChange?: (file: File | undefined) => void;
  previewDimensions?: { width: number; height: number } | null;
  selectedType?: App.Platform.Enums.ScreenshotType;
  supportsUpscaledScreenshots?: boolean;
}

export const ScreenshotDropZone: FC<ScreenshotDropZoneProps> = ({
  fileInputRef,
  hasConsistencyWarning,
  hasPreview,
  is1xCapture,
  isResolutionValid,
  onDrop,
  onFileChange,
  previewDimensions,
  previewUrl,
  selectedType,
  supportsUpscaledScreenshots,
}) => {
  const { t } = useTranslation();

  const [isDragOver, setIsDragOver] = useState(false);

  const { height: dropZoneHeight, ref: dropZoneContentRef } = useContentHeight();

  const handleDrop = (e: DragEvent) => {
    e.preventDefault();

    setIsDragOver(false);
    onDrop?.(e);
  };

  return (
    <m.button
      type="button"
      animate={{ height: dropZoneHeight }}
      transition={{ duration: 0.3, ease: [0.4, 0, 0.2, 1] }}
      className={cn(
        'relative w-full cursor-pointer overflow-hidden rounded-lg border-2 border-dashed transition-colors',
        isDragOver
          ? 'border-neutral-400 bg-neutral-800'
          : 'border-neutral-700 hover:border-neutral-500 light:border-neutral-300 light:hover:border-neutral-400',
      )}
      onClick={() => fileInputRef.current?.click()}
      onDragOver={(e) => {
        e.preventDefault();
        setIsDragOver(true);
      }}
      onDragLeave={() => setIsDragOver(false)}
      onDrop={handleDrop}
    >
      <input
        ref={fileInputRef}
        type="file"
        aria-label={t('Upload screenshot file')}
        accept={supportsUpscaledScreenshots ? '.png,.jpeg,.jpg,.webp' : '.png'}
        className="hidden"
        onChange={(event) => {
          onFileChange?.(event.target.files?.[0]);
        }}
      />

      {/* Inner content wrapper, measured by ResizeObserver */}
      <div ref={dropZoneContentRef} className="flex flex-col items-center p-4">
        {hasPreview ? (
          <m.div
            key={previewUrl}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.3, delay: 0.1 }}
            className="flex w-full flex-col items-center gap-3"
          >
            <img src={previewUrl!} alt="Preview" className="max-h-48 rounded-sm object-contain" />

            {previewDimensions ? (
              <ScreenshotPreviewMeta
                hasConsistencyWarning={hasConsistencyWarning}
                height={previewDimensions.height}
                is1xCapture={is1xCapture}
                isResolutionValid={isResolutionValid}
                selectedType={selectedType}
                supportsUpscaledScreenshots={supportsUpscaledScreenshots}
                width={previewDimensions.width}
              />
            ) : null}

            <p className="text-xs text-neutral-500">{t('Click or drag to replace')}</p>
          </m.div>
        ) : (
          <EmptyState supportsUpscaledScreenshots={supportsUpscaledScreenshots} />
        )}
      </div>
    </m.button>
  );
};

interface EmptyStateProps {
  supportsUpscaledScreenshots?: boolean;
}

const EmptyState: FC<EmptyStateProps> = ({ supportsUpscaledScreenshots }) => {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center gap-3 py-8">
      <LuUpload className="h-8 w-8 text-neutral-500" />

      <div className="flex flex-col items-center gap-1">
        <p className="text-sm text-neutral-300 light:text-neutral-600">
          {t('Drop your screenshot here, or click to browse')}
        </p>

        {supportsUpscaledScreenshots ? (
          <p className="text-center text-xs text-balance text-neutral-500">
            {t('Upscaled screenshots look sharper. Render at 2x or 3x in your emulator.')}
          </p>
        ) : (
          <p className="text-center text-xs text-balance text-neutral-500">
            {t("Use your emulator's screenshot tool. Don't manually resize.")}
          </p>
        )}
      </div>
    </div>
  );
};

/**
 * Track the scroll height of an element via ResizeObserver so we
 * can feed it into a motion `animate` for smooth height transitions.
 */
function useContentHeight() {
  const contentRef = useRef<HTMLDivElement>(null);
  const [height, setHeight] = useState<number | 'auto'>('auto');

  const observerRef = useRef<ResizeObserver | null>(null);

  const attachObserver = (node: HTMLDivElement | null) => {
    // Clean up previous observer.
    if (observerRef.current) {
      observerRef.current.disconnect();
    }

    if (!node) {
      return;
    }

    contentRef.current = node;
    setHeight(node.scrollHeight);

    observerRef.current = new ResizeObserver(() => {
      setHeight(contentRef.current!.scrollHeight);
    });
    observerRef.current.observe(node);
  };

  useEffect(() => {
    return () => observerRef.current?.disconnect();
  }, []);

  return { height, ref: attachObserver };
}
