import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface RichPresenceMessageProps {
  gameTitle: string;

  message?: string | null;
}

export const RichPresenceMessage: FC<RichPresenceMessageProps> = ({ message, gameTitle }) => {
  const { t } = useTranslation();

  if (!message || message.includes('Unknown macro')) {
    return <span title={message ?? undefined}>{t('⚠️ Playing {{gameTitle}}', { gameTitle })}</span>;
  }

  return <span>{message}</span>;
};
