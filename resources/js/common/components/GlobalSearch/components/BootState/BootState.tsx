import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSearch } from 'react-icons/lu';

/**
 * TODO previous searches? trending searches?
 */

export const BootState: FC = () => {
  const { t } = useTranslation();

  return (
    <motion.div
      data-testid="boot-state"
      className="flex flex-col items-center justify-center gap-3 text-sm"
      variants={containerVariants}
      initial="hidden"
      animate="visible"
    >
      <motion.div variants={itemVariants}>
        <LuSearch className="size-12 opacity-50" />
      </motion.div>

      <motion.p variants={itemVariants} className="text-balance">
        {t('Search for games, hubs, users, events, and achievements')}
      </motion.p>

      <motion.p variants={itemVariants} className="text-xs">
        {t('Type at least 3 characters to begin')}
      </motion.p>
    </motion.div>
  );
};

const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: {
      staggerChildren: 0.06,
      delayChildren: 0.05,
    },
  },
};

const itemVariants = {
  hidden: { opacity: 0, transform: 'translateY(5px)' },
  visible: {
    opacity: 1,
    transform: 'translateY(0px)',
  },
};
