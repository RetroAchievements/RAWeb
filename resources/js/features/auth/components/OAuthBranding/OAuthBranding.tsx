import type { TargetAndTransition, VariantLabels } from 'motion/react';
import { motion } from 'motion/react';
import type { FC } from 'react';

interface OAuthBrandingProps {
  initial?: boolean | TargetAndTransition | VariantLabels;
}

export const OAuthBranding: FC<OAuthBrandingProps> = ({ initial }) => {
  return (
    <motion.div
      className="mb-8 flex items-center gap-3"
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4, ease: 'easeOut' }}
      initial={initial}
    >
      <img className="h-10" src="/assets/images/ra-icon.webp" alt="retroachievements" />
    </motion.div>
  );
};
