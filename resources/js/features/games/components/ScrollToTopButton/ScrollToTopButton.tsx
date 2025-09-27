import { AnimatePresence, motion } from 'motion/react';
import type { FC } from 'react';
import { LuArrowUp } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

import { useCanShowScrollToTopButton } from '../../hooks/useCanShowScrollToTopButton';

export const ScrollToTopButton: FC = () => {
  const canShow = useCanShowScrollToTopButton();

  const handleClick = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <AnimatePresence>
      {canShow ? (
        <motion.button
          initial={{ opacity: 0, scale: 0.85 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.85 }}
          transition={{ duration: 0.1 }}
          onClick={handleClick}
          className={cn(
            'fixed bottom-8 right-8 z-50 mx-[-18px] flex size-12',
            'sm:bottom-6 sm:right-6',
            'items-center justify-center rounded-full bg-neutral-700 shadow-xl',
            'light:border light:border-neutral-400 light:bg-neutral-300',
            'transition-colors hover:bg-black/80',
          )}
          aria-label="scroll to top"
        >
          <LuArrowUp className="size-5 text-white light:text-neutral-600" />
        </motion.button>
      ) : null}
    </AnimatePresence>
  );
};
