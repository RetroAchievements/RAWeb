import type { TargetAndTransition, VariantLabels } from 'motion/react';
import { motion } from 'motion/react';
import type { FC } from 'react';

type GlowVariant = 'default' | 'success' | 'error';

const glowColors: Record<GlowVariant, string> = {
  default: '59, 130, 246', // blue
  success: '34, 197, 94', // green
  error: '239, 68, 68', // red
};

interface OAuthBackgroundGlowProps {
  initial?: boolean | TargetAndTransition | VariantLabels;
  variant?: GlowVariant;
}

export const OAuthBackgroundGlow: FC<OAuthBackgroundGlowProps> = ({
  initial,
  variant = 'default',
}) => {
  const rgb = glowColors[variant];

  return (
    <motion.div
      data-testid="glow"
      className="pointer-events-none fixed left-1/2 top-[5vh] h-[600px] w-[600px] -translate-x-1/2"
      style={{
        background: `radial-gradient(circle, rgba(${rgb}, 0.08) 0%, rgba(${rgb}, 0.02) 40%, transparent 70%)`,
      }}
      initial={initial}
      animate={{ opacity: 1 }}
      transition={{ duration: 1, ease: 'easeOut' }}
    />
  );
};
