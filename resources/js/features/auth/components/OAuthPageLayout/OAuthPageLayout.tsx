import type { TargetAndTransition, VariantLabels } from 'motion/react';
import { motion } from 'motion/react';
import type { FC, ReactNode } from 'react';

import { OAuthBackgroundGlow } from '../OAuthBackgroundGlow';
import { OAuthBranding } from '../OAuthBranding';

type GlowVariant = 'default' | 'success' | 'error';

interface OAuthPageLayoutProps {
  children: ReactNode;
  glowVariant?: GlowVariant;

  /**
   * Set to false to skip entrance animations (useful when returning from a redirect).
   */
  initial?: boolean | TargetAndTransition | VariantLabels;
}

export const OAuthPageLayout: FC<OAuthPageLayoutProps> = ({
  children,
  glowVariant = 'default',
  initial,
}) => {
  return (
    <div className="relative flex h-screen flex-col items-center overflow-hidden px-4 pt-[10vh] sm:pt-[25vh]">
      <OAuthBackgroundGlow variant={glowVariant} initial={initial} />
      <OAuthBranding initial={initial} />

      <motion.div
        className="z-10 w-full max-w-sm"
        initial={initial}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4, delay: 0.1, ease: 'easeOut' }}
      >
        {children}
      </motion.div>
    </div>
  );
};
