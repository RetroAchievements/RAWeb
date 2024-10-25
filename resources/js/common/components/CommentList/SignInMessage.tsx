import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

export const SignInMessage: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="mt-4 text-center">
      <p>
        {t('You must')} <a href={route('login')}>{t('sign in')}</a>{' '}
        {t('before you can join this conversation.')}
      </p>
    </div>
  );
};
