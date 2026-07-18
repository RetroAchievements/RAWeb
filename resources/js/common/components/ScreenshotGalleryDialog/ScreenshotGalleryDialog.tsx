/* eslint-disable no-restricted-imports -- this component is a unique dialog type */

import * as DialogPrimitive from '@radix-ui/react-dialog';
import { AnimatePresence, motion } from 'motion/react';
import type { FC } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RxCross2 } from 'react-icons/rx';
import { useUpdateEffect } from 'react-use';

import { cn } from '@/common/utils/cn';

import { getIsScrollbarGutterClick } from './getIsScrollbarGutterClick';
import { ScreenshotGalleryImage } from './ScreenshotGalleryImage';

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

  const imageRefs = useRef<Map<number, HTMLElement>>(new Map());

  // Bumping a session id on each open transition force-remounts the children with fresh state.
  const [openSessionId, setOpenSessionId] = useState(0);
  useUpdateEffect(() => {
    if (isOpen) {
      setOpenSessionId((id) => id + 1);
    }
  }, [isOpen]);

  const registerScrollTarget = (id: number, el: HTMLElement | null) => {
    if (el) {
      imageRefs.current.set(id, el);
    } else {
      imageRefs.current.delete(id);
    }
  };

  // Scroll to the initially-clicked image when the dialog opens.
  useEffect(() => {
    if (!isOpen || initialIndex <= 0) {
      return;
    }

    requestAnimationFrame(() => {
      const targetScreenshot = screenshots[initialIndex];
      const el = imageRefs.current.get(targetScreenshot.id);
      el?.scrollIntoView({ block: 'start' });
    });
  }, [isOpen, initialIndex, screenshots]);

  return (
    <DialogPrimitive.Root open={isOpen} onOpenChange={onOpenChange}>
      <AnimatePresence>
        {isOpen ? (
          <DialogPrimitive.Portal forceMount>
            <DialogPrimitive.Content asChild forceMount aria-describedby={undefined}>
              {/*
               * Backdrop
               * We use bg-black with a high z-index to guarantee the page underneath
               * the dialog cannot flash through if the scroll container briefly misses
               * a composite frame during scroll.
               */}
              <div className="fixed inset-0 bg-black outline-hidden" style={{ zIndex: 9999 }}>
                <motion.div
                  className="flex h-full flex-col items-center overflow-y-auto"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.15, ease: 'easeOut' }}
                  onClick={(e) => {
                    // Close when clicking the dark area around the images, but
                    // ignore clicks on the native scrollbar gutter.
                    if (e.target !== e.currentTarget || getIsScrollbarGutterClick(e)) {
                      return;
                    }

                    onOpenChange(false);
                  }}
                >
                  <DialogPrimitive.Title className="sr-only">
                    {t('Screenshot Gallery')}
                  </DialogPrimitive.Title>

                  {/* Top bar with a close button */}
                  <div className="sticky top-0 z-10 flex w-full items-center justify-center bg-black/95 py-2.5">
                    <div className="flex w-full max-w-5xl justify-end">
                      <DialogPrimitive.Close
                        className={cn(
                          'flex size-8 items-center justify-center rounded-full',
                          'text-neutral-100 transition-colors',
                          'hover:text-white focus:outline-hidden',
                        )}
                      >
                        <RxCross2 className="size-6" />
                        <span className="sr-only">{t('Close')}</span>
                      </DialogPrimitive.Close>
                    </div>
                  </div>

                  {/* Stacked images */}
                  <div className="pointer-events-none flex w-full max-w-5xl flex-col gap-4 pt-4 pb-8 sm:gap-6">
                    {screenshots.map((screenshot) => (
                      <ScreenshotGalleryImage
                        key={`${screenshot.id}-${openSessionId}`}
                        screenshot={screenshot}
                        hasAnalogTvOutput={hasAnalogTvOutput}
                        hasBeatenGame={hasBeatenGame}
                        isPixelated={isPixelated}
                        registerScrollTarget={registerScrollTarget}
                      />
                    ))}
                  </div>
                </motion.div>
              </div>
            </DialogPrimitive.Content>
          </DialogPrimitive.Portal>
        ) : null}
      </AnimatePresence>
    </DialogPrimitive.Root>
  );
};
