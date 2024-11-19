import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface RichPresenceMessageProps {
  message: string;
  gameTitle: string;
}

export const RichPresenceMessage: FC<RichPresenceMessageProps> = ({ message, gameTitle }) => {
  const { t } = useTranslation();

  if (message.includes('Unknown macro')) {
    return <span title={message}>{t('⚠️ Playing {{gameTitle}}', { gameTitle })}</span>;
  }

  return <span>{message}</span>;
};
