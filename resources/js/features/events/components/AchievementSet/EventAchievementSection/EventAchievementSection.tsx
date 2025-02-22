import * as motion from 'motion/react-m';
import type { FC, ReactNode } from 'react';

interface EventAchievementSectionProps {
  children: ReactNode;
  title: string;
}

export const EventAchievementSection: FC<EventAchievementSectionProps> = ({ children, title }) => {
  return (
    <motion.li
      className="flex flex-col gap-2.5"
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 10 }}
      transition={{
        duration: 0.12,
        delay: 0.03, // Tiny delay to let previous items finish exiting.
      }}
    >
      <div className="rounded bg-embed px-3 py-1.5 text-sm font-medium text-neutral-300 light:text-neutral-700">
        {title}
      </div>
      <ul className="flex flex-col gap-2.5">{children}</ul>
    </motion.li>
  );
};
