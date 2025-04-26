import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';

interface ManageButtonProps {
  /** Filament named routes are excluded from the front-end type mappings for performance reasons. */
  href: string;

  className?: string;
}

export const ManageButton: FC<ManageButtonProps> = ({ className, href }) => {
  const { t } = useTranslation();

  return (
    <a
      href={href}
      className={baseButtonVariants({
        size: 'sm',
        className: cn('gap-1', className),
      })}
      target="_blank"
    >
      {t('Manage')}
      <LuWrench className="size-4" />
    </a>
  );
};
