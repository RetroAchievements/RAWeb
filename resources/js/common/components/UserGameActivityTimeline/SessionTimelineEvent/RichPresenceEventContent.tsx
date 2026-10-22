import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuRadio } from 'react-icons/lu';

interface RichPresenceEventContentProps {
  label: string;
}

export const RichPresenceEventContent: FC<RichPresenceEventContentProps> = ({ label }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-1.5 text-text" title={t('Rich Presence')}>
      <LuRadio className="size-5 min-w-5" />

      <p className="line-clamp-1" title={label}>
        {label}
      </p>
    </div>
  );
};
