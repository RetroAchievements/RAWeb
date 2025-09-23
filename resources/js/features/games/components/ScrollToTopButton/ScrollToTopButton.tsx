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
            'fixed left-1/2 top-12 z-50 mx-[-18px] flex size-10 -translate-x-1/2',
            'items-center justify-center rounded-full bg-neutral-950 shadow-xl transition-colors hover:bg-black/80',
          )}
          aria-label="scroll to top"
        >
          <LuArrowUp className="size-5 text-white" />
        </motion.button>
      ) : null}
    </AnimatePresence>
  );
};
