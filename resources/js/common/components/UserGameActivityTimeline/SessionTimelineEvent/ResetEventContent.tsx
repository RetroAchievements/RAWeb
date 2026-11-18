import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface ResetEventContentProps {
  label: string;
}

export const ResetEventContent: FC<ResetEventContentProps> = ({ label }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-1.5 text-text" title={t('Reset')}>
      <p className="line-clamp-1 text-text-danger" title={label}>
        {label}
      </p>
    </div>
  );
};
