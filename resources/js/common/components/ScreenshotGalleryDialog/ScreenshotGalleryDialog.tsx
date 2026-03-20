/* eslint-disable no-restricted-imports -- this component is a unique dialog type */

import * as DialogPrimitive from '@radix-ui/react-dialog';
import { AnimatePresence, motion } from 'motion/react';
import type { FC } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuEye } from 'react-icons/lu';
import { RxCross2 } from 'react-icons/rx';

import { cn } from '@/common/utils/cn';

interface ScreenshotGalleryDialogProps {
  onOpenChange: (open: boolean) => void;
  screenshots: App.Platform.Data.GameScreenshot[];

  hasAnalogTvOutput?: boolean;
  hasBeatenGame?: boolean;
  initialIndex?: number;
  isOpen: boolean;
  isPixelated?: boolean;
}

export const ScreenshotGalleryDialog: FC<ScreenshotGalleryDialogProps> = ({
  hasAnalogTvOutput,
  isOpen,
  isPixelated,
  onOpenChange,
  screenshots,
  hasBeatenGame = false,
  initialIndex = 0,
}) => {
  const { t } = useTranslation();

  const imageRefs = useRef<Map<number, HTMLDivElement>>(new Map());

  const [revealedCompletionIds, setRevealedCompletionIds] = useState<Set<number>>(new Set());

  const handleRevealCompletion = (screenshotId: number) => {
    setRevealedCompletionIds((prev) => new Set(prev).add(screenshotId));
  };

  // Scroll to the initially-clicked image when the dialog opens,
  // and reset any previously revealed spoilers.
  useEffect(() => {
    if (!isOpen) {
      return;
    }

    setRevealedCompletionIds(new Set());

    if (initialIndex <= 0) {
      return;
    }

    requestAnimationFrame(() => {
      const targetScreenshot = screenshots[initialIndex];
      const el = imageRefs.current.get(targetScreenshot.id);
      el?.scrollIntoView({ block: 'start' });
    });
  }, [isOpen, initialIndex, screenshots]);

  const aspectRatio = hasAnalogTvOutput ? '4 / 3' : undefined;

  return (
    <DialogPrimitive.Root open={isOpen} onOpenChange={onOpenChange}>
      <AnimatePresence>
        {isOpen ? (
          <DialogPrimitive.Portal forceMount>
            {/* Backdrop */}
            <DialogPrimitive.Overlay asChild forceMount>
              <motion.div
                className="fixed inset-0 bg-black/90"
                style={{ zIndex: 9998 }}
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15, ease: 'easeOut' }}
              />
            </DialogPrimitive.Overlay>

            {/* Content (a scrollable vertical image list) */}
            <DialogPrimitive.Content asChild forceMount aria-describedby={undefined}>
              <motion.div
                className="fixed inset-0 flex flex-col items-center overflow-y-auto outline-none"
                style={{ zIndex: 9999 }}
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.12, ease: 'easeOut' }}
                onClick={(e) => {
                  // Close when clicking the backdrop area around the images.
                  if (e.target === e.currentTarget) {
                    onOpenChange(false);
                  }
                }}
              >
                <DialogPrimitive.Title className="sr-only">
                  {t('Screenshot Gallery')}
                </DialogPrimitive.Title>

                {/* Top bar with a close button */}
                <div className="sticky top-0 z-10 flex w-full items-center justify-center bg-black/95 py-2.5 backdrop-blur-sm">
                  <div className="flex w-full max-w-4xl justify-end px-4">
                    <DialogPrimitive.Close
                      className={cn(
                        'flex size-8 items-center justify-center rounded-full',
                        'text-neutral-100 transition-colors',
                        'hover:text-white focus:outline-none',
                      )}
                    >
                      <RxCross2 className="size-6" />
                      <span className="sr-only">{t('Close')}</span>
                    </DialogPrimitive.Close>
                  </div>
                </div>

                {/* Stacked images */}
                <div className="flex w-full max-w-4xl flex-col gap-4 px-4 pb-8 sm:gap-6 sm:px-6">
                  {screenshots.map((screenshot, index) => {
                    const isCompletion = screenshot.type === 'completion';
                    // Players who have already beaten the game have seen the
                    // ending, so spoiler protection would just add friction.
                    const isRevealed = hasBeatenGame || revealedCompletionIds.has(screenshot.id);

                    // Don't stagger images at or before the scroll target.
                    // They're already in view, so animating them causes
                    // them to briefly clip behind the sticky navbar.
                    const shouldStagger = index > initialIndex;

                    return (
                      <motion.div
                        key={screenshot.id}
                        ref={(el) => {
                          if (el) {
                            imageRefs.current.set(screenshot.id, el);
                          } else {
                            imageRefs.current.delete(screenshot.id);
                          }
                        }}
                        className="relative scroll-mt-20 overflow-hidden rounded"
                        initial={
                          shouldStagger ? { opacity: 0, y: 16 } : { opacity: 0, scale: 0.96 }
                        }
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        transition={
                          shouldStagger
                            ? {
                                y: { type: 'spring', stiffness: 300, damping: 28 },
                                opacity: { duration: 0.2 },
                                delay: 0.04 + (index - initialIndex) * 0.05,
                              }
                            : {
                                scale: { type: 'spring', stiffness: 400, damping: 30 },
                                opacity: { duration: 0.15 },
                              }
                        }
                      >
                        <picture>
                          <source type="image/avif" srcSet={screenshot.lgAvifUrl} />
                          <source type="image/webp" srcSet={screenshot.lgWebpUrl} />

                          <img
                            src={screenshot.lgWebpUrl}
                            alt={isCompletion ? t('Completion screenshot') : ''}
                            className={cn(
                              'w-full rounded transition-[filter] duration-300 ease-out',
                              isCompletion && !isRevealed && 'blur-3xl',
                            )}
                            style={{
                              ...(isPixelated ? { imageRendering: 'pixelated' } : {}),
                              ...(aspectRatio ? { aspectRatio } : {}),
                            }}
                            loading="lazy"
                          />
                        </picture>

                        {isCompletion && !isRevealed ? (
                          <button
                            type="button"
                            aria-label={t('Reveal completion screenshot')}
                            className={cn(
                              'absolute inset-0 flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded',
                              'bg-black/60 transition-colors hover:bg-black/70',
                            )}
                            onClick={() => handleRevealCompletion(screenshot.id)}
                          >
                            <span className="text-xs uppercase tracking-widest text-neutral-300">
                              {t('Completion screenshot')}
                            </span>

                            <span className="flex items-center gap-1.5 text-sm font-semibold text-white">
                              <LuEye className="size-4" />
                              <span className="sm:hidden">{t('Tap to reveal')}</span>
                              <span className="hidden sm:inline">{t('Click to reveal')}</span>
                            </span>
                          </button>
                        ) : null}
                      </motion.div>
                    );
                  })}
                </div>
              </motion.div>
            </DialogPrimitive.Content>
          </DialogPrimitive.Portal>
        ) : null}
      </AnimatePresence>
    </DialogPrimitive.Root>
  );
};
