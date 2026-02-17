import { AnimatePresence, motion } from 'motion/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { responsiveHeaderChipClassNames } from '@/common/utils/responsiveHeaderChipClassNames';

interface ResponsiveManageChipProps {
  className?: string;
}

export const ResponsiveManageChip: FC<ResponsiveManageChipProps> = ({ className }) => {
  const { can, game } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isHovered, setIsHovered] = useState(false);

  return (
    <a
      href={can.updateGame ? `/manage/games/${game.id}/edit` : `/manage/games/${game.id}`}
      target="_blank"
      aria-label={t('Manage')}
      className={cn(responsiveHeaderChipClassNames, '!gap-0 !rounded-full !px-2.5', className)}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <LuWrench className="size-3.5 sm:size-4" />

      <AnimatePresence>
        {isHovered ? (
          <motion.span
            initial={{ width: 0, opacity: 0, marginLeft: 0 }}
            animate={{ width: 'auto', opacity: 1, marginLeft: 6 }}
            exit={{ width: 0, opacity: 0, marginLeft: 0 }}
            transition={{ type: 'spring', duration: 0.3, bounce: 0 }}
            className="overflow-hidden whitespace-nowrap text-xs font-medium sm:text-sm"
          >
            {t('Manage')}
          </motion.span>
        ) : null}
      </AnimatePresence>
    </a>
  );
};
